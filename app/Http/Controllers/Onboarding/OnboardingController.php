<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use App\Models\Industry;
use App\Models\Tenant;
use App\Services\IndustryConfigurationService;
use App\Services\TenantDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    protected TenantDatabaseService $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function index(): Response
    {
        $user = auth()->user();
        $tenant = $user ? Tenant::find($user->tenant_id) : null;

        $industries = Industry::active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug', 'icon', 'description', 'color']);

        $defaultColumns = $this->databaseService->getPresetColumnsForIndustry();

        return Inertia::render('Onboarding/Index', [
            'industries' => $industries,
            'defaultColumns' => $defaultColumns,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
        ]);
    }

    public function getBusinessTypes(Request $request): JsonResponse
    {
        $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
        ]);

        $types = BusinessType::where('industry_id', $request->industry_id)
            ->active()
            ->orderBy('display_order')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json($types);
    }

    public function getIndustryColumns(Request $request): JsonResponse
    {
        $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
        ]);

        $industry = Industry::find($request->industry_id);
        $columns = $this->databaseService->getPresetColumnsForIndustry($industry);

        return response()->json($columns);
    }

    public function complete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'business_type_id' => ['nullable', 'integer', 'exists:business_types,id'],
            'employee_range' => ['nullable', 'string', 'max:20'],
            'crm_goals' => ['nullable', 'array'],
            'selected_columns' => ['nullable', 'array'],
        ]);

        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);

        if (!$tenant) {
            return redirect('/login')->withErrors(['error' => 'Organization not found.']);
        }

        // Update tenant with onboarding data
        $tenant->update([
            'industry_id' => $validated['industry_id'],
            'business_type_id' => $validated['business_type_id'] ?? null,
            'employee_range' => $validated['employee_range'] ?? null,
            'crm_goals' => $validated['crm_goals'] ?? [],
            'onboarding_completed' => true,
        ]);

        // 1. Provision dynamic MySQL database and custom columns table
        $columns = $validated['selected_columns'] ?? [];
        $this->databaseService->createTenantDatabase($tenant, $columns);

        // 2. Provision CRM modules, pipelines, and industry settings
        $configService = new IndustryConfigurationService();
        $configService->provision($tenant);

        return redirect('/tenant/crm-records')->with('success', "Your CRM database has been created with custom columns! Welcome to {$tenant->name}.");
    }
}
