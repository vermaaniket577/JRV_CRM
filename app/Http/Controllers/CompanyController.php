<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\IndustrySchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    /**
     * Display Company & Corporate Accounts Directory
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id ?? ($request->hasSession() ? $request->session()->get('current_tenant_id') : null);
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;

        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryConfig = IndustrySchemaService::getIndustryConfig($industrySlug);

        $query = Company::with(['owner', 'contacts', 'deals'])
            ->where(function ($q) use ($tenantId) {
                if ($tenantId) {
                    $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                }
            });

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        $companies = $query->latest()->get();

        // If table is completely empty, seed a few sector-accurate starter companies
        if ($companies->isEmpty()) {
            $this->seedStarterCompanies($tenantId, $industrySlug);
            $companies = Company::with(['owner', 'contacts', 'deals'])
                ->where(function ($q) use ($tenantId) {
                    if ($tenantId) {
                        $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                    }
                })->latest()->get();
        }

        $metrics = [
            'total_companies' => $companies->count(),
            'total_deals' => $companies->sum(fn($c) => $c->deals->count()),
            'total_contacts' => $companies->sum(fn($c) => $c->contacts->count()),
            'total_revenue' => $companies->sum('annual_revenue'),
        ];

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'metrics' => $metrics,
            'industryConfig' => $industryConfig,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created Company
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'employee_count' => ['nullable', 'numeric'],
            'annual_revenue' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        Company::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'owner_id' => $user?->id,
        ]));

        return redirect()->back()->with('success', "Company '{$validated['name']}' created successfully.");
    }

    /**
     * Update existing Company
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'employee_count' => ['nullable', 'numeric'],
            'annual_revenue' => ['nullable', 'numeric'],
        ]);

        $company->update($validated);

        return redirect()->back()->with('success', "Company '{$company->name}' updated successfully.");
    }

    /**
     * Delete Company
     */
    public function destroy(Company $company): RedirectResponse
    {
        $name = $company->name;
        $company->delete();

        return redirect()->back()->with('success', "Company '{$name}' deleted.");
    }

    private function seedStarterCompanies(?int $tenantId, string $industrySlug): void
    {
        if ($industrySlug === 'insurance') {
            $starters = [
                ['name' => 'Tata AIG General Insurance', 'domain' => 'tataaig.com', 'industry' => 'Underwriting & Risk', 'phone' => '+91 22 6665 8282', 'website' => 'https://www.tataaig.com', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'employee_count' => 8500, 'annual_revenue' => 125000000],
                ['name' => 'HDFC ERGO Health Network', 'domain' => 'hdfcergo.com', 'industry' => 'Health & Mediclaim TPA', 'phone' => '+91 22 6234 6234', 'website' => 'https://www.hdfcergo.com', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'country' => 'India', 'employee_count' => 12000, 'annual_revenue' => 340000000],
                ['name' => 'Star Health Corporate Solutions', 'domain' => 'starhealth.in', 'industry' => 'Retail & Group Mediclaim', 'phone' => '+91 44 2828 8800', 'website' => 'https://www.starhealth.in', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'country' => 'India', 'employee_count' => 14000, 'annual_revenue' => 280000000],
            ];
        } elseif ($industrySlug === 'healthcare') {
            $starters = [
                ['name' => 'Apollo Hospitals Enterprise', 'domain' => 'apollohospitals.com', 'industry' => 'Hospital & Diagnostics', 'phone' => '+1 (555) 345-6789', 'website' => 'https://www.apollohospitals.com', 'city' => 'New York', 'state' => 'NY', 'country' => 'USA', 'employee_count' => 45000, 'annual_revenue' => 850000000],
                ['name' => 'Memorial Sloan Kettering', 'domain' => 'mskcc.org', 'industry' => 'Oncology Research', 'phone' => '+1 (555) 456-7890', 'website' => 'https://www.mskcc.org', 'city' => 'Boston', 'state' => 'MA', 'country' => 'USA', 'employee_count' => 18000, 'annual_revenue' => 420000000],
            ];
        } else {
            $starters = [
                ['name' => 'Acme Global Enterprise', 'domain' => 'acme.org', 'industry' => 'Corporate Accounts', 'phone' => '+1 (555) 123-4567', 'website' => 'https://www.acme.org', 'city' => 'San Francisco', 'state' => 'CA', 'country' => 'USA', 'employee_count' => 2500, 'annual_revenue' => 65000000],
                ['name' => 'Apex Solutions Ltd', 'domain' => 'apexsolutions.io', 'industry' => 'Enterprise Services', 'phone' => '+1 (555) 987-6543', 'website' => 'https://www.apexsolutions.io', 'city' => 'Chicago', 'state' => 'IL', 'country' => 'USA', 'employee_count' => 1200, 'annual_revenue' => 32000000],
            ];
        }

        foreach ($starters as $s) {
            Company::create(array_merge($s, ['tenant_id' => $tenantId]));
        }
    }
}
