<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantDatabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RegisterController extends Controller
{
    public function showRegistrationForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request, TenantDatabaseService $dbService): SymfonyResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_name' => ['required', 'string', 'max:255'],
            'subdomain' => ['nullable', 'string', 'max:50', 'alpha_dash'],
        ]);

        // 1. Generate clean, unique company subdomain
        $rawSub = $validated['subdomain'] ?: preg_replace('/[^a-zA-Z0-9]/', '', strtolower($validated['organization_name']));
        $cleanSub = substr(preg_replace('/[^a-zA-Z0-9]/', '', strtolower($rawSub)), 0, 30);
        if (!$cleanSub) $cleanSub = 'crm' . rand(100, 999);

        $baseSub = $cleanSub;
        $counter = 1;
        while (Tenant::where('subdomain', $cleanSub)->exists()) {
            $cleanSub = $baseSub . $counter;
            $counter++;
        }

        // 2. Create tenant (organization) record with subdomain
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => $validated['organization_name'],
            'slug' => Str::slug($validated['organization_name']) . '-' . Str::random(4),
            'subdomain' => $cleanSub,
            'status' => 'active',
            'onboarding_completed' => false,
            'trial_ends_at' => now()->addDays(14),
        ]);

        // 3. Immediately create separate dedicated MySQL database for this company
        $dbService->createTenantDatabase($tenant);

        // 4. Create admin user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_tenant_admin' => true,
            'status' => 'active',
        ]);

        Auth::login($user);

        session(['tenant_id' => $tenant->id]);

        // Generate secure short-lived transfer token for seamless authentication on the subdomain
        $authToken = Str::random(40);
        \Illuminate\Support\Facades\Cache::put("subdomain_auth_{$authToken}", $user->id, now()->addMinutes(5));

        // Directly land on the subdomain URL (e.g. http://unlockrentals.localhost:8000/)
        $subdomainTargetUrl = $tenant->getSubdomainUrl('/', [
            'auth_token' => $authToken,
            'onboarding_success' => 1,
        ]);

        if ($request->header('X-Inertia')) {
            return Inertia::location($subdomainTargetUrl);
        }

        return redirect()->away($subdomainTargetUrl);
    }
}
