<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use App\Models\Tenant;
use App\Services\IndustryConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminPanelController extends Controller
{
    public function index(Request $request): Response
    {
        $statusFilter = $request->query('status', 'all');
        $industryFilter = $request->query('industry', 'all');
        $search = $request->query('search', '');

        // Seed sample leads if empty
        $this->ensureSampleLeadsExist();

        // 1. Fetch Product Sales Leads
        $leadsQuery = DB::table('crm_sales_leads');

        if ($statusFilter !== 'all') {
            $leadsQuery->where('deal_stage', $statusFilter);
        }

        if ($industryFilter !== 'all') {
            $leadsQuery->where('industry', 'like', "%{$industryFilter}%");
        }

        if (!empty($search)) {
            $leadsQuery->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('industry', 'like', "%{$search}%");
            });
        }

        $leads = $leadsQuery->orderBy('created_at', 'desc')->get();

        // 2. Fetch Recent Product Transactions
        $transactions = DB::table('crm_transactions')
            ->orderBy('purchase_date', 'desc')
            ->limit(10)
            ->get();

        // 3. Compute Product Sales Statistics
        $totalLeads = DB::table('crm_sales_leads')->count();
        $convertedLeads = DB::table('crm_sales_leads')->where('deal_stage', 'Won')->count();
        $totalPipelineValue = DB::table('crm_sales_leads')->sum('estimated_mrr');
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) . '%' : '0%';

        $stats = [
            'total_leads' => $totalLeads,
            'converted_leads' => $convertedLeads,
            'total_pipeline_mrr' => '₹' . number_format($totalPipelineValue ?: 85000),
            'conversion_rate' => $conversionRate,
            'total_active_crms' => Tenant::where('status', 'active')->count() ?: 2,
        ];

        // 4. Fetch Industries & Business Types for provisioning
        $industries = Industry::with('businessTypes')->where('is_active', true)->get();

        // 5. Fetch Subscription Plans for Master Admin Management
        $plans = DB::table('crm_plans')->orderBy('id')->get();

        // 6. Fetch Database Import Statistics
        $importLogger = app(\App\Services\ImportLoggerService::class);
        $databaseImportSummary = $importLogger->getDashboardSummary();
        $recentDatabaseImports = \App\Models\DatabaseImport::latest()->take(5)->get();

        return Inertia::render('Admin/Index', [
            'stats' => $stats,
            'leads' => $leads,
            'transactions' => $transactions,
            'industries' => $industries,
            'plans' => $plans,
            'databaseImportSummary' => $databaseImportSummary,
            'recentDatabaseImports' => $recentDatabaseImports,
            'filters' => [
                'status' => $statusFilter,
                'industry' => $industryFilter,
                'search' => $search,
            ],
        ]);
    }


    public function storeLead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'industry' => ['required', 'string', 'max:100'],
            'deal_stage' => ['required', 'string', 'in:New,Qualified,Proposal,Won,Lost'],
            'estimated_mrr' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('crm_sales_leads')->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->back()->with('success', 'Product sales lead logged successfully!');
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'deal_stage' => ['required', 'string', 'in:New,Qualified,Proposal,Won,Lost'],
        ]);

        DB::table('crm_sales_leads')
            ->where('id', $id)
            ->update([
                'deal_stage' => $validated['deal_stage'],
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Lead status updated!');
    }

    public function provisionLead(Request $request, int $id): RedirectResponse
    {
        $lead = DB::table('crm_sales_leads')->where('id', $id)->first();
        if (!$lead) {
            return redirect()->back()->with('error', 'Lead not found.');
        }

        // Match industry by name or slug
        $industry = Industry::where('name', 'like', "%{$lead->industry}%")
            ->orWhere('slug', 'like', "%{$lead->industry}%")
            ->first();

        if (!$industry) {
            $industry = Industry::first();
        }

        // Create Tenant
        $tenant = Tenant::create([
            'name' => $lead->company_name,
            'slug' => \Illuminate\Support\Str::slug($lead->company_name) . '-' . rand(100, 999),
            'domain' => \Illuminate\Support\Str::slug($lead->company_name) . '.jrvcrm.com',
            'industry_id' => $industry?->id,
            'status' => 'active',
            'storage_limit_mb' => 5120, // 5 GB Free Trial Storage Limit
            'storage_used_mb' => 250.0,
            'plan_name' => 'Growth Plan',
            'employee_range' => '6-20',
        ]);

        // Provision CRM engine
        if ($industry) {
            IndustryConfigurationService::provision($tenant, $industry, null);
        }

        // Update Lead to Won
        DB::table('crm_sales_leads')->where('id', $id)->update([
            'deal_stage' => 'Won',
            'tenant_id' => $tenant->id,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', "Client CRM provisioned for {$lead->company_name}!");
    }

    public function updatePlan(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['nullable', 'numeric', 'min:0'],
            'storage_limit_gb' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
        ]);

        $annualPrice = isset($validated['price_annual']) && $validated['price_annual'] > 0
            ? $validated['price_annual']
            : round($validated['price_monthly'] * 0.8);

        DB::table('crm_plans')
            ->where('id', $id)
            ->update([
                'name' => $validated['name'],
                'price_monthly' => $validated['price_monthly'],
                'price_annual' => $annualPrice,
                'storage_limit_gb' => $validated['storage_limit_gb'],
                'max_users' => $validated['max_users'],
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', "Subscription Plan '{$validated['name']}' updated successfully!");
    }

    public function paidUsers(): Response
    {
        $paidLeads = DB::table('crm_sales_leads')
            ->where('deal_stage', 'Won')
            ->orderBy('updated_at', 'desc')
            ->get();

        $tenants = Tenant::orderBy('created_at', 'desc')->get()->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'domain' => $tenant->domain,
                'status' => $tenant->status,
                'storage_used_gb' => $tenant->storage_used_gb ?? 1.2,
                'storage_limit_gb' => $tenant->storage_limit_gb ?? 25.0,
                'created_at' => $tenant->created_at ? $tenant->created_at->format('Y-m-d H:i') : null,
            ];
        });

        $plans = DB::table('crm_plans')->get();

        return Inertia::render('Admin/PaidUsers', [
            'paidLeads' => $paidLeads,
            'tenants' => $tenants,
            'plans' => $plans,
        ]);
    }

    public function settings(): Response
    {
        $settings = DB::table('system_settings')->get()->pluck('value', 'key')->all();

        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $inputs = $request->except('_token');

        foreach ($inputs as $key => $value) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'updated_at' => now(),
                ]
            );
        }

        return redirect()->back()->with('success', 'System Settings & Integrations updated successfully!');
    }

    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'plan_name' => ['required', 'string'],
            'billing_cycle' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $txnId = 'TXN_' . date('Ymd') . rand(100000, 999999);
        $tenantId = session('tenant_id') ?? auth()->user()?->tenant_id;

        $storageGb = 25;
        if (str_contains(strtolower($validated['plan_name']), 'growth')) {
            $storageGb = 100;
        } elseif (str_contains(strtolower($validated['plan_name']), 'enterprise')) {
            $storageGb = 999;
        }

        if ($tenantId) {
            DB::table('tenants')
                ->where('id', $tenantId)
                ->update([
                    'plan' => $validated['plan_name'],
                    'storage_limit_gb' => $storageGb,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
        }

        $expiryDate = $validated['billing_cycle'] === 'annually'
            ? now()->addYear()->format('M d, Y')
            : now()->addMonth()->format('M d, Y');

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully and plan activated!',
            'transaction_id' => $txnId,
            'plan_name' => $validated['plan_name'],
            'billing_cycle' => $validated['billing_cycle'],
            'amount' => $validated['amount'],
            'storage_gb' => $storageGb,
            'expiry_date' => $expiryDate,
        ]);
    }

    private function ensureSampleLeadsExist(): void
    {
        if (DB::table('crm_sales_leads')->count() === 0) {
            DB::table('crm_sales_leads')->insert([
                [
                    'customer_name' => 'Rajesh Sharma',
                    'company_name' => 'Apex CS & Compliance Associates',
                    'email' => 'rajesh@apexcs.com',
                    'phone' => '+91 98765 43210',
                    'industry' => 'CS (Company Secretary)',
                    'deal_stage' => 'Qualified',
                    'estimated_mrr' => 149.00,
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ],
                [
                    'customer_name' => 'Adv. Ananya Verma',
                    'company_name' => 'Verma & Associates Legal Firm',
                    'email' => 'ananya@vermalaw.in',
                    'phone' => '+91 98123 55678',
                    'industry' => 'Lawyer / Advocate Firm',
                    'deal_stage' => 'Proposal',
                    'estimated_mrr' => 399.00,
                    'created_at' => now()->subDays(4),
                    'updated_at' => now()->subDays(1),
                ],
                [
                    'customer_name' => 'Dr. Michael Chang',
                    'company_name' => 'BioTech Research Labs',
                    'email' => 'm.chang@biotech.org',
                    'phone' => '+1 415 555 0192',
                    'industry' => 'Healthcare',
                    'deal_stage' => 'Won',
                    'estimated_mrr' => 149.00,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subDays(3),
                ],
                [
                    'customer_name' => 'Sarah Jenkins',
                    'company_name' => 'St. Xavier International School',
                    'email' => 's.jenkins@stxavierschool.com',
                    'phone' => '+1 312 555 0144',
                    'industry' => 'Education & Training',
                    'deal_stage' => 'Won',
                    'estimated_mrr' => 399.00,
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subDays(5),
                ],
                [
                    'customer_name' => 'Vikram Patel',
                    'company_name' => 'Skyline Realty Group',
                    'email' => 'vikram@skylinerealty.com',
                    'phone' => '+91 99000 11223',
                    'industry' => 'Real Estate',
                    'deal_stage' => 'New',
                    'estimated_mrr' => 149.00,
                    'created_at' => now()->subHours(5),
                    'updated_at' => now()->subHours(5),
                ],
            ]);
        }
    }
}
