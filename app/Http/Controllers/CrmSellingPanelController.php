<?php

namespace App\Http\Controllers;

use App\Models\BusinessType;
use App\Models\Industry;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\IndustryConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CrmSellingPanelController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = Tenant::with(['industry', 'businessType', 'subscription.plan'])
            ->withCount('users')
            ->latest()
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                    'currency' => $tenant->currency ?? 'USD',
                    'primary_color' => $tenant->primary_color_hex ?? '#4F46E5',
                    'onboarding_completed' => $tenant->onboarding_completed,
                    'employee_range' => $tenant->employee_range ?? '1-10',
                    'users_count' => $tenant->users_count,
                    'created_at' => $tenant->created_at->format('M d, Y'),
                    'industry' => $tenant->industry ? [
                        'id' => $tenant->industry->id,
                        'name' => $tenant->industry->name,
                        'icon' => $tenant->industry->icon,
                        'color' => $tenant->industry->color,
                    ] : null,
                    'business_type' => $tenant->businessType ? $tenant->businessType->name : null,
                    'plan' => $tenant->subscription?->plan ? $tenant->subscription->plan->name : 'Growth Plan',
                ];
            });

        $industries = Industry::active()
            ->orderBy('display_order')
            ->with('businessTypes')
            ->get(['id', 'name', 'slug', 'icon', 'description', 'color']);

        $plans = SubscriptionPlan::all();
        if ($plans->isEmpty()) {
            $plans = collect([
                ['id' => 1, 'name' => 'Starter Plan', 'price_monthly' => '49.00', 'max_users' => 5],
                ['id' => 2, 'name' => 'Growth Plan', 'price_monthly' => '149.00', 'max_users' => 25],
                ['id' => 3, 'name' => 'Enterprise Plan', 'price_monthly' => '399.00', 'max_users' => 100],
            ]);
        }

        $metrics = [
            'total_crms' => $tenants->count(),
            'active_crms' => $tenants->where('status', 'active')->count(),
            'trial_crms' => $tenants->where('onboarding_completed', false)->count(),
            'total_users' => User::count(),
            'monthly_revenue' => '₹' . number_format(($tenants->count() ?: 2) * 14900),
            'top_industry' => 'Education & Training',
        ];

        return Inertia::render('CrmSellingPanel/Index', [
            'tenants' => $tenants,
            'industries' => $industries,
            'plans' => $plans,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Provision a brand new CRM SaaS instance for a client based on their required industry sector.
     */
    public function provisionCrm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:6'],
            'plan_name' => ['nullable', 'string'],
            'employee_range' => ['nullable', 'string'],
        ]);

        // 1. Create Tenant (CRM Workspace)
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => $validated['organization_name'],
            'slug' => Str::slug($validated['organization_name']) . '-' . Str::random(4),
            'industry_id' => $validated['industry_id'],
            'business_type_id' => $validated['business_type_id'] ?? null,
            'employee_range' => $validated['employee_range'] ?? '6-20',
            'status' => 'active',
            'storage_limit_mb' => 5120, // 5 GB Free Trial Storage Limit
            'storage_used_mb' => 350.0,
            'onboarding_completed' => true,
            'trial_ends_at' => now()->addDays(30),
        ]);

        // 2. Create Tenant Admin Account
        User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['admin_name'],
            'email' => $validated['admin_email'],
            'password' => Hash::make($validated['admin_password']),
            'is_tenant_admin' => true,
            'status' => 'active',
        ]);

        // 3. Provision CRM Modules, Pipelines, and Settings for the selected industry
        $configService = new IndustryConfigurationService();
        $configService->provision($tenant);

        return redirect()->back()->with('success', "CRM provisioned successfully for {$tenant->name}!");
    }

    /**
     * Re-assign or switch industry sector for an existing CRM instance.
     */
    public function assignIndustry(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
        ]);

        $newIndustry = Industry::findOrFail($validated['industry_id']);

        $configService = new IndustryConfigurationService();
        $configService->switchIndustry($tenant, $newIndustry);

        return redirect()->back()->with('success', "Reconfigured CRM for {$tenant->name} to {$newIndustry->name}!");
    }

    /**
     * Launch/Impersonate a tenant's CRM workspace.
     */
    public function launchWorkspace(Tenant $tenant): RedirectResponse
    {
        session(['tenant_id' => $tenant->id]);

        return redirect('/')->with('success', "Switched workspace to {$tenant->name}.");
    }
}
