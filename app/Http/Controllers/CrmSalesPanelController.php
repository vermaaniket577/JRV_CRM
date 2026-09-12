<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CrmSalesPanelController extends Controller
{
    public function index(Request $request): Response
    {
        $planFilter = $request->query('plan', 'all'); // all, Starter, Pro, Enterprise
        $statusFilter = $request->query('status', 'all'); // all, Paid, Pending, Refunded
        $search = $request->query('search', '');
        $activeTab = $request->query('tab', 'dashboard'); // dashboard, leads, deals, subscriptions, customers, analytics, settings

        // 1. Fetch Transactions (Recent CRM Sales)
        $txQuery = DB::table('crm_transactions');

        if ($planFilter !== 'all') {
            $txQuery->where('plan_tier', $planFilter);
        }

        if ($statusFilter !== 'all') {
            $txQuery->where('payment_status', $statusFilter);
        }

        if (!empty($search)) {
            $txQuery->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('transaction_code', 'like', "%{$search}%");
            });
        }

        $transactions = $txQuery->orderBy('purchase_date', 'desc')->get();

        // 2. Fetch Leads (Deals Pipeline)
        $leads = DB::table('crm_sales_leads')->get();

        // 3. Compute Metrics
        $totalSalesCount = DB::table('crm_transactions')->where('payment_status', 'Paid')->count();
        $totalRevenue = (float) DB::table('crm_transactions')->where('payment_status', 'Paid')->sum('amount');
        $activeSubscriptions = DB::table('tenants')->where('status', 'active')->count();
        $mrr = $totalRevenue;
        $conversionRate = $totalSalesCount > 0 ? '100%' : '0%';

        $stats = [
            'total_sales' => '₹' . number_format($totalRevenue, 2),
            'total_sales_count' => $totalSalesCount,
            'active_subscriptions' => $activeSubscriptions,
            'mrr' => '₹' . number_format($mrr, 2),
            'conversion_rate' => $conversionRate,
        ];

        // 4. Fetch CRM Plans
        $plans = DB::table('crm_plans')->get();

        // 5. Fetch Customers List from Transactions & Tenants
        $customers = DB::table('crm_transactions')
            ->select(
                'customer_name',
                'customer_email',
                'plan_tier',
                'payment_status',
                DB::raw('count(*) as orders_count'),
                DB::raw('sum(amount) as total_spent'),
                DB::raw('max(purchase_date) as last_activity')
            )
            ->groupBy('customer_name', 'customer_email', 'plan_tier', 'payment_status')
            ->orderBy('total_spent', 'desc')
            ->get();

        $tenants = DB::table('tenants')
            ->select('id', 'name', 'subdomain', 'status', 'created_at')
            ->get();

        return Inertia::render('CrmSalesPanel/Index', [
            'stats' => $stats,
            'transactions' => $transactions,
            'leads' => $leads,
            'plans' => $plans,
            'customers' => $customers,
            'tenants' => $tenants,
            'filters' => [
                'plan' => $planFilter,
                'status' => $statusFilter,
                'search' => $search,
                'tab' => $activeTab,
            ],
        ]);
    }

    public function storeLead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'industry' => ['required', 'string', 'max:100'],
            'deal_stage' => ['required', 'string', 'in:New,Qualified,Proposal,Won,Lost'],
            'estimated_mrr' => ['required', 'numeric', 'min:0'],
        ]);

        DB::table('crm_sales_leads')->insert(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->back()->with('success', 'Lead added to Deals Pipeline!');
    }

    public function updateLeadStage(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'deal_stage' => ['required', 'string', 'in:New,Qualified,Proposal,Won,Lost'],
        ]);

        DB::table('crm_sales_leads')->where('id', $id)->update([
            'deal_stage' => $validated['deal_stage'],
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Deal stage updated!');
    }
}
