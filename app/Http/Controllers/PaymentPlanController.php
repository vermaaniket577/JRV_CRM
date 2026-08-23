<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentPlanController extends Controller
{
    /**
     * Display a listing of customer payment plans & invoices
     */
    public function index(Request $request): Response
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id ?? 7;
        $tenant = Tenant::with('industry')->find($tenantId);

        $query = PaymentPlan::with(['installments', 'contact', 'user'])
            ->where('tenant_id', $tenantId)
            ->latest();

        // Search Filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->query('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $plans = $query->paginate(15)->withQueryString();

        // Calculate KPI Metrics
        $allTenantPlans = PaymentPlan::with('installments')->where('tenant_id', $tenantId)->get();
        $totalInvoiced = (float) $allTenantPlans->sum('total_amount');
        
        $totalCollected = 0;
        foreach ($allTenantPlans as $plan) {
            $totalCollected += $plan->paid_amount;
        }

        $totalPending = max(0, $totalInvoiced - $totalCollected);
        $totalPlansCount = $allTenantPlans->count();
        $paidPlansCount = $allTenantPlans->where('status', 'paid')->count();
        $pendingPlansCount = $allTenantPlans->whereIn('status', ['pending', 'partially_paid'])->count();
        $overduePlansCount = $allTenantPlans->where('status', 'overdue')->count();

        // Fetch Contacts for Quick Select
        $contacts = Contact::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'email', 'phone')
            ->latest()
            ->limit(100)
            ->get();

        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);
        $businessIcon = TenantSetting::getByKey('business_icon', $tenant?->industry?->icon ?? '🎓', $tenantId);

        return Inertia::render('PaymentPlans/Index', [
            'plans' => $plans,
            'contacts' => $contacts,
            'filters' => [
                'search' => $request->query('search', ''),
                'status' => $request->query('status', 'all'),
            ],
            'kpis' => [
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'total_pending' => $totalPending,
                'total_plans_count' => $totalPlansCount,
                'paid_plans_count' => $paidPlansCount,
                'pending_plans_count' => $pendingPlansCount,
                'overdue_plans_count' => $overduePlansCount,
            ],
            'business' => [
                'name' => $businessName,
                'icon' => $businessIcon,
                'tenant_id' => $tenantId,
            ]
        ]);
    }

    /**
     * Store a newly created payment plan / invoice for a customer
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id ?? 7;

        $validated = $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['nullable', 'string', 'max:10'],
            'plan_type' => ['required', 'in:one_time,installments'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'installments' => ['nullable', 'array'],
            'installments.*.title' => ['nullable', 'string', 'max:255'],
            'installments.*.amount' => ['required_with:installments', 'numeric', 'min:1'],
            'installments.*.due_date' => ['nullable', 'date'],
        ]);

        // 1. Ensure Contact exists in CRM
        $contactId = $validated['contact_id'] ?? null;
        if (!$contactId && Schema::hasTable('contacts')) {
            $parts = explode(' ', $validated['customer_name'], 2);
            $contact = Contact::updateOrCreate(
                ['email' => $validated['customer_email']],
                [
                    'tenant_id' => $tenantId,
                    'first_name' => $parts[0],
                    'last_name' => $parts[1] ?? 'Customer',
                    'phone' => $validated['customer_phone'],
                    'source' => 'Payment Plan Invoice Generated',
                ]
            );
            $contactId = $contact->id;
        }

        $invoiceNumber = 'INV-' . date('Y') . '-' . strtoupper(Str::random(6));
        $paymentToken = 'pay_' . Str::random(32);

        $plan = PaymentPlan::create([
            'tenant_id' => $tenantId,
            'contact_id' => $contactId,
            'user_id' => $request->user()?->id,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'title' => $validated['title'],
            'total_amount' => $validated['total_amount'],
            'currency' => $validated['currency'] ?? 'INR',
            'plan_type' => $validated['plan_type'],
            'status' => 'pending',
            'payment_token' => $paymentToken,
            'due_date' => $validated['due_date'] ?? now()->addDays(7)->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        // 2. Create Installments
        if ($validated['plan_type'] === 'installments' && !empty($validated['installments'])) {
            foreach ($validated['installments'] as $idx => $inst) {
                PaymentPlanInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $idx + 1,
                    'title' => $inst['title'] ?: ("Installment " . ($idx + 1)),
                    'amount' => $inst['amount'],
                    'due_date' => $inst['due_date'] ?? now()->addDays(30 * ($idx + 1))->toDateString(),
                    'status' => 'pending',
                ]);
            }
        } else {
            // Single one-time installment
            PaymentPlanInstallment::create([
                'payment_plan_id' => $plan->id,
                'installment_number' => 1,
                'title' => 'Full Payment',
                'amount' => $validated['total_amount'],
                'due_date' => $validated['due_date'] ?? now()->addDays(7)->toDateString(),
                'status' => 'pending',
            ]);
        }

        return redirect()->route('payment-plans.show', $plan->id)
            ->with('success', "Payment plan {$invoiceNumber} created successfully! Link is ready to share.");
    }

    /**
     * Display a specific payment plan, invoice & installment timeline
     */
    public function show(PaymentPlan $paymentPlan): Response
    {
        $paymentPlan->load(['installments', 'contact', 'user']);
        $tenantId = $paymentPlan->tenant_id;
        $tenant = Tenant::with('industry')->find($tenantId);

        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);
        $businessIcon = TenantSetting::getByKey('business_icon', $tenant?->industry?->icon ?? '🎓', $tenantId);
        $brandColor = TenantSetting::getByKey('brand_color', '#dc2626', $tenantId);
        $contactPhone = TenantSetting::getByKey('contact_phone', '+91 98765 43210', $tenantId);
        $supportEmail = TenantSetting::getByKey('support_email', 'support@admissionsdekho.com', $tenantId);

        // Bank details for printable invoice
        $bankName = TenantSetting::getByKey('bank_name', 'HDFC Bank Ltd', $tenantId);
        $bankAccountHolder = TenantSetting::getByKey('bank_account_holder', $businessName, $tenantId);
        $bankAccountNumber = TenantSetting::getByKey('bank_account_number', '50200084920194', $tenantId);
        $bankIfscCode = TenantSetting::getByKey('bank_ifsc_code', 'HDFC0001234', $tenantId);
        $upiId = TenantSetting::getByKey('upi_id', 'admissionsdekho@okaxis', $tenantId);

        return Inertia::render('PaymentPlans/Show', [
            'plan' => $paymentPlan,
            'business' => [
                'name' => $businessName,
                'icon' => $businessIcon,
                'brand_color' => $brandColor,
                'contact_phone' => $contactPhone,
                'support_email' => $supportEmail,
                'bank_name' => $bankName,
                'bank_account_holder' => $bankAccountHolder,
                'bank_account_number' => $bankAccountNumber,
                'bank_ifsc_code' => $bankIfscCode,
                'upi_id' => $upiId,
            ]
        ]);
    }

    /**
     * Manually mark an installment as Paid (Cash / Offline wire)
     */
    public function markInstallmentPaid(Request $request, PaymentPlanInstallment $installment): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $installment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $validated['payment_method'],
            'transaction_ref' => $validated['transaction_ref'] ?: ('MANUAL-' . date('YmdHis')),
            'notes' => $validated['notes'] ?: 'Recorded manually from User Panel',
        ]);

        $plan = $installment->plan;
        $plan->recalculateStatus();

        // Create or update Won deal in CRM
        if (Schema::hasTable('deals') && Schema::hasTable('pipelines')) {
            $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
            $stage = null;
            if ($pipeline) {
                $stage = PipelineStage::where('pipeline_id', $pipeline->id)->where('stage_type', 'won')->first() 
                    ?? PipelineStage::where('pipeline_id', $pipeline->id)->first();
            }

            if ($pipeline && $stage) {
                Deal::create([
                    'tenant_id' => $plan->tenant_id,
                    'contact_id' => $plan->contact_id,
                    'title' => "{$plan->title} - {$installment->title} ({$plan->customer_name})",
                    'value' => $installment->amount,
                    'currency' => $plan->currency,
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'closed_at' => now(),
                ]);
            }
        }

        return redirect()->back()
            ->with('success', "Installment #{$installment->installment_number} (₹" . number_format($installment->amount, 2) . ") marked as Paid!");
    }

    /**
     * Cancel or delete a payment plan
     */
    public function destroy(PaymentPlan $paymentPlan): RedirectResponse
    {
        $invoiceNum = $paymentPlan->invoice_number;
        $paymentPlan->delete();

        return redirect()->route('payment-plans.index')
            ->with('success', "Payment plan {$invoiceNum} deleted successfully.");
    }
}
