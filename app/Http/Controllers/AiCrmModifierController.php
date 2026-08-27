<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantCustomColumn;
use App\Models\TenantSetting;
use App\Services\AiCrmGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiCrmModifierController extends Controller
{
    protected AiCrmGeneratorService $aiService;

    public function __construct(AiCrmGeneratorService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::with(['industry', 'businessType'])->find($tenantId) : null;

        // Check if tenant is on a paid plan
        $isPaid = (bool) (
            TenantSetting::getByKey('is_paid_plan', 'false', $tenantId) === 'true' ||
            in_array(TenantSetting::getByKey('subscription_tier', 'free', $tenantId), ['starter', 'growth', 'enterprise', 'custom', 'pro']) ||
            ($tenant && $tenant->subscription && $tenant->subscription->payment_status === 'paid')
        );

        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);
        $industryName = $tenant?->industry?->name ?? ucwords($industrySlug);
        $industryIcon = $tenant?->industry?->icon ?? '⚡';

        $currentConfig = [
            'business_name' => TenantSetting::getByKey('business_name', $tenant?->name ?? "JRV {$industryName} CRM", $tenantId),
            'business_icon' => TenantSetting::getByKey('business_icon', $industryIcon, $tenantId),
            'brand_color' => TenantSetting::getByKey('brand_color', 'teal', $tenantId),
            'specialty_name' => TenantSetting::getByKey('specialty_name', "{$industryName} Enterprise", $tenantId),
            'last_prompt' => TenantSetting::getByKey('ai_crm_last_prompt', '', $tenantId),
            'last_updated_at' => TenantSetting::getByKey('ai_crm_last_updated_at', null, $tenantId),
            'custom_fields_count' => TenantCustomColumn::where('tenant_id', $tenantId)->count(),
        ];

        $plans = DB::table('crm_plans')->where('is_active', true)->get();
        $presets = $this->aiService->getPresetTemplates($industrySlug);

        return Inertia::render('AiCrmModifier', [
            'isPaidUser' => $isPaid,
            'currentConfig' => $currentConfig,
            'presets' => $presets,
            'plans' => $plans,
            'currentTier' => TenantSetting::getByKey('subscription_tier', $isPaid ? 'Growth Pro' : 'Free Trial', $tenantId),
        ]);
    }

    /**
     * AI Generation Endpoint
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:1000'],
            'preset_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::with('industry')->find($tenantId) : null;
        $industrySlug = $tenant?->industry?->slug ?? TenantSetting::getByKey('industry_slug', 'insurance', $tenantId);

        $prompt = $validated['prompt'] ?? 'CRM Workspace';
        $presetId = $validated['preset_id'] ?? null;

        $generated = $this->aiService->generateFromPrompt($prompt, $presetId, $industrySlug);

        return response()->json([
            'success' => true,
            'config' => $generated,
        ]);
    }

    /**
     * Apply Generated AI Schema to Workspace (Paid Users Only)
     */
    public function apply(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        $isPaid = (bool) (
            TenantSetting::getByKey('is_paid_plan', 'false', $tenantId) === 'true' ||
            in_array(TenantSetting::getByKey('subscription_tier', 'free', $tenantId), ['starter', 'growth', 'enterprise', 'custom', 'pro']) ||
            ($tenant && $tenant->subscription && $tenant->subscription->payment_status === 'paid')
        );

        if (!$isPaid) {
            return redirect()->back()->with('error', '🔒 AI CRM Customizer is exclusively available for Paid Plan subscribers. Please upgrade to unlock.');
        }

        $validated = $request->validate([
            'config' => ['required', 'array'],
            'config.name' => ['required', 'string'],
            'config.icon' => ['nullable', 'string'],
            'config.color' => ['nullable', 'string'],
            'config.specialty' => ['nullable', 'string'],
            'config.prompt' => ['nullable', 'string'],
            'config.custom_fields' => ['nullable', 'array'],
            'config.nav_items' => ['nullable', 'array'],
            'config.pipeline_stages' => ['nullable', 'array'],
        ]);

        $result = $this->aiService->applyToWorkspace($validated['config'], $tenantId);

        return redirect()->back()->with('success', "🤖 AI Successfully deployed {$validated['config']['name']} to your CRM workspace!");
    }

    /**
     * Activate Paid Plan / Instant Demo Pro Unlock for testing
     */
    public function activatePaid(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tenantId = session('tenant_id') ?? $user?->tenant_id;

        $plan = $request->input('tier', 'growth');
        TenantSetting::setByKey('is_paid_plan', 'true', $tenantId);
        TenantSetting::setByKey('subscription_tier', $plan, $tenantId);

        return redirect()->back()->with('success', "🎉 Workspace upgraded to {$plan} Paid Plan! AI CRM Studio is now fully unlocked.");
    }
}
