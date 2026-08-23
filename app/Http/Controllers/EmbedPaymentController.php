<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Member;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanInstallment;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EmbedPaymentController extends Controller
{
    /**
     * Display the 1-Click Public Payment & Fee Collection Portal
     */
    public function show(Request $request, ?string $tenantParam = null): Response
    {
        $tenantId = $request->query('tenant_id') 
            ?? $request->query('tenant') 
            ?? $tenantParam 
            ?? session('tenant_id') 
            ?? $request->user()?->tenant_id 
            ?? 7;

        if (!is_numeric($tenantId)) {
            $matchedTenant = Tenant::where('slug', $tenantId)->orWhere('name', 'like', "%{$tenantId}%")->first();
            $tenantId = $matchedTenant?->id ?? 7;
        }

        $tenant = Tenant::with('industry')->find($tenantId);

        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);
        $businessIcon = TenantSetting::getByKey('business_icon', $tenant?->industry?->icon ?? '🎓', $tenantId);
        $brandColor = TenantSetting::getByKey('brand_color', '#dc2626', $tenantId);
        $supportEmail = TenantSetting::getByKey('support_email', 'support@admissionsdekho.com', $tenantId);
        $contactPhone = TenantSetting::getByKey('contact_phone', '+91 98765 43210', $tenantId);
        $whatsappNumber = TenantSetting::getByKey('whatsapp_number', $contactPhone, $tenantId);

        // Payment Settings
        $gateway = TenantSetting::getByKey('payment_gateway', 'upi', $tenantId);
        $currency = TenantSetting::getByKey('currency', 'INR', $tenantId);
        $upiId = TenantSetting::getByKey('upi_id', 'admissionsdekho@okaxis', $tenantId);
        $upiMerchantName = TenantSetting::getByKey('upi_merchant_name', $businessName, $tenantId);
        $upiQrCodeUrl = TenantSetting::getByKey('upi_qr_code_url', '', $tenantId);

        // Corporate Bank Details
        $bankName = TenantSetting::getByKey('bank_name', 'HDFC Bank Ltd', $tenantId);
        $bankAccountHolder = TenantSetting::getByKey('bank_account_holder', $businessName, $tenantId);
        $bankAccountNumber = TenantSetting::getByKey('bank_account_number', '50200084920194', $tenantId);
        $bankIfscCode = TenantSetting::getByKey('bank_ifsc_code', 'HDFC0001234', $tenantId);
        $bankAccountType = TenantSetting::getByKey('bank_account_type', 'Current Account', $tenantId);
        $bankBranch = TenantSetting::getByKey('bank_branch', 'Connaught Place, New Delhi', $tenantId);
        $bankSwiftCode = TenantSetting::getByKey('bank_swift_code', 'HDFCINBB', $tenantId);
        $bankInstructions = TenantSetting::getByKey('bank_instructions', 'Please enter candidate/student name in transaction remarks and share the UTR reference number.', $tenantId);

        // Gateway Keys (Public)
        $razorpayKey = TenantSetting::getByKey('razorpay_key', '', $tenantId);
        $stripeKey = TenantSetting::getByKey('stripe_key', '', $tenantId);

        // Prefilled query parameters
        $amount = (float) ($request->query('amount') ?? $request->query('fee') ?? 1000);
        $purpose = $request->query('purpose') ?? $request->query('course') ?? $request->query('item') ?? 'Admission Registration & Processing Fee';
        $customerName = $request->query('name') ?? $request->query('student_name') ?? '';
        $customerEmail = $request->query('email') ?? '';
        $customerPhone = $request->query('phone') ?? '';
        $invoiceRef = $request->query('ref') ?? $request->query('invoice') ?? ('INV-' . date('Ymd') . '-' . rand(1000, 9999));

        return Inertia::render('Embed/PaymentPortal', [
            'tenantId' => $tenantId,
            'business' => [
                'name' => $businessName,
                'icon' => $businessIcon,
                'brand_color' => $brandColor,
                'support_email' => $supportEmail,
                'contact_phone' => $contactPhone,
                'whatsapp_number' => $whatsappNumber,
            ],
            'payment' => [
                'gateway' => $gateway,
                'currency' => $currency,
                'upi_id' => $upiId,
                'upi_merchant_name' => $upiMerchantName,
                'upi_qr_code_url' => $upiQrCodeUrl,
                'bank_name' => $bankName,
                'bank_account_holder' => $bankAccountHolder,
                'bank_account_number' => $bankAccountNumber,
                'bank_ifsc_code' => $bankIfscCode,
                'bank_account_type' => $bankAccountType,
                'bank_branch' => $bankBranch,
                'bank_swift_code' => $bankSwiftCode,
                'bank_instructions' => $bankInstructions,
                'razorpay_key' => $razorpayKey,
                'stripe_key' => $stripeKey,
            ],
            'preset' => [
                'amount' => $amount,
                'purpose' => $purpose,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'invoice_ref' => $invoiceRef,
            ]
        ]);
    }

    /**
     * Submit Payment Proof / Transaction Reference from Customer
     */
    public function store(Request $request): RedirectResponse
    {
        $tenantId = $request->input('tenant_id') ?? 7;
        $tenant = Tenant::find($tenantId);
        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:1'],
            'purpose' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:upi,bank_transfer,razorpay,stripe,card'],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'utr_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $proofUrl = null;
        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $filename = 'proof_' . time() . '_' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/payments'), $filename);
            $proofUrl = '/uploads/payments/' . $filename;
        }

        $receiptNumber = 'RCP-' . date('Ymd') . '-' . rand(10000, 99999);
        $transactionId = $validated['utr_number'] ?: ($validated['transaction_ref'] ?: ('TXN-' . time() . '-' . rand(100, 999)));

        // 1. Create or Update Contact in CRM
        $parts = explode(' ', $validated['customer_name'], 2);
        $firstName = $parts[0];
        $lastName = $parts[1] ?? 'Customer';

        $contact = null;
        if (Schema::hasTable('contacts')) {
            $contact = Contact::updateOrCreate(
                ['email' => $validated['customer_email']],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $validated['customer_phone'],
                    'source' => 'Website Payment Gateway (' . strtoupper($validated['payment_method']) . ')',
                ]
            );
        }

        // 2. Also register in Deals / Billing Pipeline
        if (Schema::hasTable('deals') && Schema::hasTable('pipelines')) {
            $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
            $stage = null;
            if ($pipeline) {
                $stage = PipelineStage::where('pipeline_id', $pipeline->id)->where('stage_type', 'won')->first() 
                    ?? PipelineStage::where('pipeline_id', $pipeline->id)->first();
            }

            if ($pipeline && $stage) {
                Deal::create([
                    'tenant_id' => $tenantId,
                    'title' => $validated['purpose'] . ' - ' . $validated['customer_name'],
                    'value' => $validated['amount'],
                    'currency' => 'INR',
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stage->id,
                    'contact_id' => $contact?->id,
                    'closed_at' => now(),
                ]);
            }
        }

        $receiptData = [
            'receipt_number' => $receiptNumber,
            'transaction_id' => $transactionId,
            'amount' => $validated['amount'],
            'purpose' => $validated['purpose'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'],
            'payment_method' => strtoupper($validated['payment_method']),
            'business_name' => $businessName,
            'timestamp' => now()->format('d M Y, h:i A'),
            'proof_url' => $proofUrl,
        ];

        return redirect()->back()
            ->with('success', 'Payment verified successfully! Receipt has been generated.')
            ->with('receipt', $receiptData);
    }

    /**
     * Customer-Facing Checkout for a specific Payment Plan / Installment Token
     */
    public function showPlan(string $token): Response
    {
        $plan = PaymentPlan::with(['installments', 'contact', 'user'])
            ->where('payment_token', $token)
            ->firstOrFail();

        $tenantId = $plan->tenant_id;
        $tenant = Tenant::with('industry')->find($tenantId);

        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);
        $businessIcon = TenantSetting::getByKey('business_icon', $tenant?->industry?->icon ?? '🎓', $tenantId);
        $brandColor = TenantSetting::getByKey('brand_color', '#dc2626', $tenantId);
        $supportEmail = TenantSetting::getByKey('support_email', 'support@admissionsdekho.com', $tenantId);
        $contactPhone = TenantSetting::getByKey('contact_phone', '+91 98765 43210', $tenantId);

        // Payment Settings
        $upiId = TenantSetting::getByKey('upi_id', 'admissionsdekho@okaxis', $tenantId);
        $upiMerchantName = TenantSetting::getByKey('upi_merchant_name', $businessName, $tenantId);
        $upiQrCodeUrl = TenantSetting::getByKey('upi_qr_code_url', '', $tenantId);

        // Bank Details
        $bankName = TenantSetting::getByKey('bank_name', 'HDFC Bank Ltd', $tenantId);
        $bankAccountHolder = TenantSetting::getByKey('bank_account_holder', $businessName, $tenantId);
        $bankAccountNumber = TenantSetting::getByKey('bank_account_number', '50200084920194', $tenantId);
        $bankIfscCode = TenantSetting::getByKey('bank_ifsc_code', 'HDFC0001234', $tenantId);
        $bankAccountType = TenantSetting::getByKey('bank_account_type', 'Current Account', $tenantId);
        $bankBranch = TenantSetting::getByKey('bank_branch', 'Connaught Place, New Delhi', $tenantId);
        $bankSwiftCode = TenantSetting::getByKey('bank_swift_code', 'HDFCINBB', $tenantId);
        $bankInstructions = TenantSetting::getByKey('bank_instructions', 'Please enter your invoice number in transaction remarks and share the UTR reference number.', $tenantId);

        // Find next pending installment
        $activeInstallment = $plan->installments->where('status', '!=', 'paid')->first() ?? $plan->installments->first();

        return Inertia::render('Embed/CustomerPlanCheckout', [
            'plan' => $plan,
            'activeInstallment' => $activeInstallment,
            'business' => [
                'name' => $businessName,
                'icon' => $businessIcon,
                'brand_color' => $brandColor,
                'support_email' => $supportEmail,
                'contact_phone' => $contactPhone,
            ],
            'payment' => [
                'upi_id' => $upiId,
                'upi_merchant_name' => $upiMerchantName,
                'upi_qr_code_url' => $upiQrCodeUrl,
                'bank_name' => $bankName,
                'bank_account_holder' => $bankAccountHolder,
                'bank_account_number' => $bankAccountNumber,
                'bank_ifsc_code' => $bankIfscCode,
                'bank_account_type' => $bankAccountType,
                'bank_branch' => $bankBranch,
                'bank_swift_code' => $bankSwiftCode,
                'bank_instructions' => $bankInstructions,
            ]
        ]);
    }

    /**
     * Customer Submits Payment for a specific Payment Plan / Installment
     */
    public function submitPlanPayment(Request $request, string $token): RedirectResponse
    {
        $plan = PaymentPlan::with('installments')->where('payment_token', $token)->firstOrFail();
        $tenantId = $plan->tenant_id;
        $tenant = Tenant::find($tenantId);
        $businessName = TenantSetting::getByKey('business_name', $tenant?->name ?? 'Admissions Dekho', $tenantId);

        $validated = $request->validate([
            'installment_id' => ['required', 'integer'],
            'payment_method' => ['required', 'string', 'in:upi,bank_transfer,razorpay,stripe,card'],
            'transaction_ref' => ['nullable', 'string', 'max:255'],
            'utr_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $installment = PaymentPlanInstallment::where('payment_plan_id', $plan->id)
            ->where('id', $validated['installment_id'])
            ->firstOrFail();

        $proofUrl = null;
        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $filename = 'proof_plan_' . time() . '_' . rand(100, 999) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/payments'), $filename);
            $proofUrl = '/uploads/payments/' . $filename;
        }

        $receiptNumber = 'RCP-' . date('Ymd') . '-' . rand(10000, 99999);
        $transactionId = $validated['utr_number'] ?: ($validated['transaction_ref'] ?: ('TXN-' . time() . '-' . rand(100, 999)));

        $installment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $validated['payment_method'],
            'transaction_ref' => $transactionId,
            'utr_number' => $validated['utr_number'],
            'proof_url' => $proofUrl,
            'notes' => $validated['notes'] ?: 'Paid by customer via Public Payment Plan Checkout',
        ]);

        $plan->recalculateStatus();

        // Register deal in pipeline
        if (Schema::hasTable('deals') && Schema::hasTable('pipelines')) {
            $pipeline = Pipeline::where('is_default', true)->first() ?? Pipeline::first();
            $stage = null;
            if ($pipeline) {
                $stage = PipelineStage::where('pipeline_id', $pipeline->id)->where('stage_type', 'won')->first() 
                    ?? PipelineStage::where('pipeline_id', $pipeline->id)->first();
            }

            if ($pipeline && $stage) {
                Deal::create([
                    'tenant_id' => $tenantId,
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

        $receiptData = [
            'receipt_number' => $receiptNumber,
            'transaction_id' => $transactionId,
            'amount' => $installment->amount,
            'purpose' => "{$plan->title} - {$installment->title}",
            'customer_name' => $plan->customer_name,
            'customer_email' => $plan->customer_email,
            'customer_phone' => $plan->customer_phone,
            'payment_method' => strtoupper($validated['payment_method']),
            'business_name' => $businessName,
            'timestamp' => now()->format('d M Y, h:i A'),
            'proof_url' => $proofUrl,
        ];

        return redirect()->back()
            ->with('success', "Payment of ₹" . number_format($installment->amount, 2) . " received successfully!")
            ->with('receipt', $receiptData);
    }
}
