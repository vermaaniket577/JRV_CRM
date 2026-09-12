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

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OnboardingController extends Controller
{
    protected TenantDatabaseService $databaseService;

    public function __construct(TenantDatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function index(): Response|SymfonyResponse
    {
        $user = auth()->user();
        $tenant = $user ? Tenant::find($user->tenant_id) : null;

        if ($tenant) {
            session(['tenant_id' => $tenant->id]);

            if ($tenant->onboarding_completed && !request()->has('reconfigure')) {
                $authToken = \Illuminate\Support\Str::random(40);
                \Illuminate\Support\Facades\Cache::put("subdomain_auth_{$authToken}", $user->id, now()->addMinutes(5));

                $subdomainTargetUrl = $tenant->getSubdomainUrl('/', [
                    'auth_token' => $authToken,
                    'onboarding_success' => 1,
                ]);

                if (request()->header('X-Inertia')) {
                    return Inertia::location($subdomainTargetUrl);
                }

                return redirect()->away($subdomainTargetUrl);
            }
        }

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
                'subdomain' => $tenant->subdomain,
                'subdomain_url' => $tenant->subdomain_url ?? ('https://' . $tenant->subdomain . '.jrvcrm.com'),
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

    /**
     * Upload database file during onboarding, deploy tables/columns,
     * auto-adapt CRM according to database, and redirect directly into CRM records.
     */
    public function uploadDatabase(Request $request): JsonResponse|SymfonyResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // up to 100MB
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
        ]);

        $user = auth()->user();
        $tenant = $user ? Tenant::find($user->tenant_id) : null;

        if (!$tenant) {
            return response()->json(['error' => 'Organization not found.'], 404);
        }

        session(['tenant_id' => $tenant->id]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $filePath = $file->getRealPath();
        $originalName = $file->getClientOriginalName();

        // If industry was pre-selected, assign it first
        if ($request->filled('industry_id')) {
            $tenant->update(['industry_id' => $request->industry_id]);
        }

        try {
            /** @var \App\Services\SqlDatabaseDeploymentService $deployService */
            $deployService = app(\App\Services\SqlDatabaseDeploymentService::class);

            if (in_array($ext, ['sql', 'dump'])) {
                $result = $deployService->deploySqlFile($tenant, $filePath, $originalName, $user->id);
            } else {
                $result = $deployService->deploySpreadsheetFile($tenant, $file, $originalName, $user->id);
            }

            // Mark onboarding as completed
            $tenant->update([
                'onboarding_completed' => true,
            ]);

            // Generate secure transfer token for subdomain authentication
            $authToken = \Illuminate\Support\Str::random(40);
            \Illuminate\Support\Facades\Cache::put("subdomain_auth_{$authToken}", $user->id, now()->addMinutes(5));

            $subdomainTargetUrl = $tenant->getSubdomainUrl('/tenant/crm-records', [
                'auth_token' => $authToken,
                'onboarding_success' => 1,
            ]);

            $detectedName = $result['detected_industry']?->name ?? 'Custom CRM';
            $message = "Your database was successfully deployed! CRM converted to '{$detectedName}' with {$result['columns_registered']} custom fields and {$result['rows_deployed']} records displayed.";

            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => $subdomainTargetUrl,
                    'details' => $result,
                ]);
            }

            return redirect()->away($subdomainTargetUrl)->with('success', $message);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Onboarding database deployment failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Database deployment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function complete(Request $request): SymfonyResponse
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

        session(['tenant_id' => $tenant->id]);

        // 1. Provision dynamic MySQL database and custom columns table
        $columns = $validated['selected_columns'] ?? [];
        $this->databaseService->createTenantDatabase($tenant, $columns);

        // 2. Provision CRM modules, pipelines, and industry settings
        $configService = new IndustryConfigurationService();
        $configService->provision($tenant);

        // Generate secure transfer token for subdomain authentication
        $authToken = \Illuminate\Support\Str::random(40);
        \Illuminate\Support\Facades\Cache::put("subdomain_auth_{$authToken}", $user->id, now()->addMinutes(5));

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
