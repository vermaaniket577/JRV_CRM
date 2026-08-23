<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    protected RazorpayService $razorpayService;

    public function __construct(RazorpayService $razorpayService)
    {
        $this->razorpayService = $razorpayService;
    }

    /**
     * Create Razorpay order for plan subscription or invoice payment.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'plan_name' => ['nullable', 'string'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,annually'],
            'receipt' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        $notes = [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant?->name ?? 'Organization',
            'user_name' => $user?->name ?? 'Admin',
            'user_email' => $user?->email ?? 'admin@crm.com',
            'plan_name' => $validated['plan_name'] ?? 'Growth Plan',
            'billing_cycle' => $validated['billing_cycle'] ?? 'monthly',
        ];

        $result = $this->razorpayService->createOrder(
            (float) $validated['amount'],
            $validated['receipt'] ?? null,
            $notes
        );

        return response()->json([
            'success' => true,
            'key_id' => $this->razorpayService->getKeyId(),
            'order' => $result['order'],
            'customer' => [
                'name' => $user?->name ?? $tenant?->name ?? 'Customer',
                'email' => $user?->email ?? 'support@jrvcrm.com',
                'phone' => '+919876543210',
            ],
            'business_name' => $tenant?->name ?? 'JRV CRM SaaS',
        ]);
    }

    /**
     * Verify payment signature and activate plan/storage upgrade.
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['nullable', 'string'],
            'plan_name' => ['required', 'string'],
            'billing_cycle' => ['nullable', 'string'],
            'amount' => ['required', 'numeric'],
        ]);

        $isValid = $this->razorpayService->verifySignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'] ?? 'mock_sig'
        );

        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Payment signature verification failed. Please contact support.',
            ], 400);
        }

        $user = auth()->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        $planName = $validated['plan_name'];
        $cycle = $validated['billing_cycle'] ?? 'monthly';
        $amount = (float) $validated['amount'];

        // Determine storage limit from plan
        $tier = strtolower(trim(str_replace('plan', '', strtolower($planName))));
        $storageMb = match ($tier) {
            'starter' => 25600, // 25 GB
            'growth', 'pro' => 102400, // 100 GB
            'enterprise', 'unlimited' => 1048576, // 1000 GB / 1 TB
            default => 102400,
        };
        $storageGb = round($storageMb / 1024);

        $expiryDate = $cycle === 'annually' ? now()->addYear() : now()->addMonth();

        // Update Tenant storage limit and plan
        if ($tenant) {
            $tenant->update([
                'storage_limit_mb' => $storageMb,
                'status' => 'active',
                'trial_ends_at' => $expiryDate,
            ]);
        }

        // Find or create plan record
        $plan = DB::table('crm_plans')->where('name', 'like', "%{$planName}%")->first();
        if ($plan && $tenant) {
            DB::table('crm_subscriptions')->updateOrInsert(
                ['tenant_id' => $tenant->id],
                [
                    'crm_plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_cycle' => $cycle,
                    'starts_at' => now(),
                    'ends_at' => $expiryDate,
                    'updated_at' => now(),
                ]
            );
        }

        // Record payment in crm_transactions
        $txnCode = 'RZP_' . $validated['razorpay_payment_id'];
        DB::table('crm_transactions')->insert([
            'tenant_id' => $tenant?->id,
            'transaction_code' => $txnCode,
            'customer_name' => $user?->name ?? $tenant?->name ?? 'Paid Customer',
            'customer_email' => $user?->email ?? 'customer@jrvcrm.com',
            'plan_tier' => ucfirst($tier ?: 'Growth'),
            'amount' => $amount,
            'payment_status' => 'Paid',
            'payment_method' => 'Razorpay (UPI / Card / NetBanking)',
            'purchase_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'transaction_id' => $txnCode,
            'payment_id' => $validated['razorpay_payment_id'],
            'order_id' => $validated['razorpay_order_id'],
            'plan_name' => $planName,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'storage_gb' => $storageGb,
            'expiry_date' => $expiryDate->format('M d, Y'),
            'message' => "Payment of ₹" . number_format($amount) . " received successfully via Razorpay! Your CRM workspace has been upgraded to {$planName} with {$storageGb} GB cloud storage.",
        ]);
    }

    /**
     * Webhook listener for async Razorpay payment events.
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        if ($signature && !$this->razorpayService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Razorpay Webhook Invalid Signature');
            return response()->json(['status' => 'invalid_signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('payload.payment.entity');

        Log::info("Razorpay Webhook Event: {$event}", ['payment_id' => $data['id'] ?? null]);

        return response()->json(['status' => 'success']);
    }
}
