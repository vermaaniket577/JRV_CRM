<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;

        $generalSettings = [
            'business_name' => TenantSetting::getByKey('business_name', $tenant?->name ?? 'JRV CRM', $tenantId),
            'business_icon' => TenantSetting::getByKey('business_icon', $tenant?->industry?->icon ?? '⚡', $tenantId),
            'brand_color' => TenantSetting::getByKey('brand_color', $tenant?->industry?->color ?? '#dc2626', $tenantId),
            'logo_url' => TenantSetting::getByKey('logo_url', '', $tenantId),
            'website_url' => TenantSetting::getByKey('website_url', 'https://mybusiness.com', $tenantId),
            'tagline' => TenantSetting::getByKey('tagline', 'Empowering Next-Gen Business Growth', $tenantId),
            'support_email' => TenantSetting::getByKey('support_email', $user?->email ?? 'support@jrvcrm.com', $tenantId),
            'contact_phone' => TenantSetting::getByKey('contact_phone', '+91 98765 43210', $tenantId),
            'whatsapp_number' => TenantSetting::getByKey('whatsapp_number', '+91 98765 43210', $tenantId),
            'address' => TenantSetting::getByKey('address', '101, Business Tower, Corporate Park', $tenantId),
            'city' => TenantSetting::getByKey('city', 'Mumbai', $tenantId),
            'state' => TenantSetting::getByKey('state', 'Maharashtra', $tenantId),
            'country' => TenantSetting::getByKey('country', 'India', $tenantId),
            'postal_code' => TenantSetting::getByKey('postal_code', '400001', $tenantId),
        ];

        $paymentSettings = [
            'gateway' => TenantSetting::getByKey('payment_gateway', 'razorpay', $tenantId),
            'is_live' => TenantSetting::getByKey('payment_is_live', 'false', $tenantId) === 'true',
            'currency' => TenantSetting::getByKey('payment_currency', 'INR', $tenantId),
            'razorpay_key' => TenantSetting::getByKey('razorpay_key_id', '', $tenantId),
            'razorpay_secret' => TenantSetting::getByKey('razorpay_key_secret', '', $tenantId),
            'razorpay_webhook_secret' => TenantSetting::getByKey('razorpay_webhook_secret', '', $tenantId),
            'stripe_key' => TenantSetting::getByKey('stripe_publishable_key', '', $tenantId),
            'stripe_secret' => TenantSetting::getByKey('stripe_secret_key', '', $tenantId),
            'upi_id' => TenantSetting::getByKey('upi_vpa_id', '', $tenantId),
            'upi_merchant_name' => TenantSetting::getByKey('upi_merchant_name', $generalSettings['business_name'], $tenantId),
            'upi_qr_code_url' => TenantSetting::getByKey('upi_qr_code_url', '', $tenantId),
            'bank_name' => TenantSetting::getByKey('bank_name', '', $tenantId),
            'bank_account_holder' => TenantSetting::getByKey('bank_account_holder', $generalSettings['business_name'], $tenantId),
            'bank_account_number' => TenantSetting::getByKey('bank_account_number', '', $tenantId),
            'bank_ifsc_code' => TenantSetting::getByKey('bank_ifsc_code', '', $tenantId),
            'bank_account_type' => TenantSetting::getByKey('bank_account_type', 'Current Account', $tenantId),
            'bank_branch' => TenantSetting::getByKey('bank_branch', '', $tenantId),
            'bank_swift_code' => TenantSetting::getByKey('bank_swift_code', '', $tenantId),
            'bank_instructions' => TenantSetting::getByKey('bank_instructions', 'Please share the payment receipt / UTR number via WhatsApp or email once transferred.', $tenantId),
        ];

        $socialSettings = [
            'instagram' => TenantSetting::getByKey('social_instagram', 'https://instagram.com/mybusiness', $tenantId),
            'facebook' => TenantSetting::getByKey('social_facebook', 'https://facebook.com/mybusiness', $tenantId),
            'youtube' => TenantSetting::getByKey('social_youtube', 'https://youtube.com/@mybusiness', $tenantId),
            'linkedin' => TenantSetting::getByKey('social_linkedin', 'https://linkedin.com/company/mybusiness', $tenantId),
            'twitter' => TenantSetting::getByKey('social_twitter', 'https://x.com/mybusiness', $tenantId),
            'whatsapp_channel' => TenantSetting::getByKey('social_whatsapp_channel', '', $tenantId),
        ];

        $appUrl = config('app.url', url('/'));

        $integrationFlow = [
            'api_endpoint' => $appUrl . '/api/v1/members/register',
            'search_endpoint' => $appUrl . '/api/v1/members/search',
            'embed_iframe_code' => '<iframe src="' . $appUrl . '/embed/register" width="100%" height="750" frameborder="0" style="border:none; border-radius:16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);"></iframe>',
            'embed_widget_script' => '<script src="' . $appUrl . '/js/jrv-crm-widget.js" data-tenant-id="' . ($tenantId ?? 1) . '" async></script>',
            'webhook_url' => $appUrl . '/api/v1/webhooks/leads',
        ];

        return Inertia::render('SystemSettings', [
            'general' => $generalSettings,
            'payments' => $paymentSettings,
            'social' => $socialSettings,
            'integration' => $integrationFlow,
            'user' => [
                'name' => $user?->name ?? 'Administrator',
                'email' => $user?->email ?? 'admin@jrvcrm.com',
            ],
            'tenant' => $tenant,
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_icon' => ['nullable', 'string', 'max:50'],
            'brand_color' => ['nullable', 'string', 'max:50'],
            'logo_url' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'whatsapp_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        foreach ($validated as $key => $val) {
            TenantSetting::setByKey($key, (string) $val, $tenantId);
        }

        // Sync tenant name and dedicated database settings if tenant exists
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $tenant->update(['name' => $validated['business_name']]);
                $dbService = new \App\Services\TenantDatabaseService();
                $dbService->syncTenantSettings($tenant, $validated);
            }
        }

        return back()->with('success', 'General business settings updated and saved directly into your database.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(6), 'confirmed'],
        ]);

        if ($user && !Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        if ($user) {
            $user->forceFill([
                'password' => Hash::make($validated['new_password']),
            ])->save();
        }

        return back()->with('success', 'Account security password changed successfully.');
    }

    public function updatePayments(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gateway' => ['required', 'string', 'in:razorpay,stripe,upi,paypal'],
            'is_live' => ['required', 'boolean'],
            'currency' => ['required', 'string', 'max:10'],
            'razorpay_key' => ['nullable', 'string', 'max:255'],
            'razorpay_secret' => ['nullable', 'string', 'max:255'],
            'razorpay_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret' => ['nullable', 'string', 'max:255'],
            'upi_id' => ['nullable', 'string', 'max:255'],
            'upi_merchant_name' => ['nullable', 'string', 'max:255'],
            'upi_qr_code_url' => ['nullable', 'string', 'max:500'],
            'qr_code_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:5120'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_ifsc_code' => ['nullable', 'string', 'max:50'],
            'bank_account_type' => ['nullable', 'string', 'max:50'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:50'],
            'bank_instructions' => ['nullable', 'string', 'max:1000'],
            'remove_qr_code' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        TenantSetting::setByKey('payment_gateway', $validated['gateway'], $tenantId);
        TenantSetting::setByKey('payment_is_live', $validated['is_live'] ? 'true' : 'false', $tenantId);
        TenantSetting::setByKey('payment_currency', $validated['currency'], $tenantId);
        TenantSetting::setByKey('razorpay_key_id', $validated['razorpay_key'] ?? '', $tenantId);
        TenantSetting::setByKey('razorpay_key_secret', $validated['razorpay_secret'] ?? '', $tenantId);
        TenantSetting::setByKey('razorpay_webhook_secret', $validated['razorpay_webhook_secret'] ?? '', $tenantId);
        TenantSetting::setByKey('stripe_publishable_key', $validated['stripe_key'] ?? '', $tenantId);
        TenantSetting::setByKey('stripe_secret_key', $validated['stripe_secret'] ?? '', $tenantId);
        TenantSetting::setByKey('upi_vpa_id', $validated['upi_id'] ?? '', $tenantId);
        TenantSetting::setByKey('upi_merchant_name', $validated['upi_merchant_name'] ?? '', $tenantId);

        // Bank Account Details
        TenantSetting::setByKey('bank_name', $validated['bank_name'] ?? '', $tenantId);
        TenantSetting::setByKey('bank_account_holder', $validated['bank_account_holder'] ?? '', $tenantId);
        TenantSetting::setByKey('bank_account_number', $validated['bank_account_number'] ?? '', $tenantId);
        TenantSetting::setByKey('bank_ifsc_code', strtoupper($validated['bank_ifsc_code'] ?? ''), $tenantId);
        TenantSetting::setByKey('bank_account_type', $validated['bank_account_type'] ?? 'Current Account', $tenantId);
        TenantSetting::setByKey('bank_branch', $validated['bank_branch'] ?? '', $tenantId);
        TenantSetting::setByKey('bank_swift_code', strtoupper($validated['bank_swift_code'] ?? ''), $tenantId);
        TenantSetting::setByKey('bank_instructions', $validated['bank_instructions'] ?? '', $tenantId);

        // Handle QR Code Image Upload
        if ($request->hasFile('qr_code_file')) {
            $file = $request->file('qr_code_file');
            $uploadDir = public_path('uploads/qr-codes');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = 'qr_' . ($tenantId ?: 'global') . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $qrUrl = '/uploads/qr-codes/' . $filename;
            TenantSetting::setByKey('upi_qr_code_url', $qrUrl, $tenantId);
        } elseif ($request->boolean('remove_qr_code')) {
            TenantSetting::setByKey('upi_qr_code_url', '', $tenantId);
        } elseif (!empty($validated['upi_qr_code_url'])) {
            TenantSetting::setByKey('upi_qr_code_url', $validated['upi_qr_code_url'], $tenantId);
        }

        return back()->with('success', 'Bank account details, UPI QR code, and payment gateway configuration updated successfully.');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instagram' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'whatsapp_channel' => ['nullable', 'url', 'max:255'],
        ]);

        $user = Auth::user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        foreach ($validated as $key => $val) {
            TenantSetting::setByKey('social_' . $key, (string) $val, $tenantId);
        }

        return back()->with('success', 'Social media integration links updated successfully.');
    }
}
