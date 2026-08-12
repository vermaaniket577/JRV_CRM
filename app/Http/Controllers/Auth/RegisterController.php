<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function showRegistrationForm(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'organization_name' => ['required', 'string', 'max:255'],
        ]);

        // Create tenant (organization)
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => $validated['organization_name'],
            'slug' => Str::slug($validated['organization_name']) . '-' . Str::random(4),
            'status' => 'active',
            'onboarding_completed' => false,
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create admin user
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

        return redirect('/onboarding');
    }
}
