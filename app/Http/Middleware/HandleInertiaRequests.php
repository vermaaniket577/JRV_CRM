<?php

namespace App\Http\Middleware;

use App\Models\NavigationItem;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $customNav = [];
        $customNavList = [];
        $tenantIndustry = null;
        $businessSettings = [
            'business_name' => 'JRV CRM',
            'business_icon' => '⚡',
            'brand_color' => 'indigo',
        ];

        try {
            $user = $request->user();
            $tenantId = session('tenant_id') ?? $user?->tenant_id;

            if ($tenantId) {
                $tenant = Tenant::with(['industry', 'businessType'])->find($tenantId);
                if ($tenant) {
                    $businessSettings['business_name'] = $tenant->name;
                    if ($tenant->industry) {
                        $businessSettings['business_icon'] = $tenant->industry->icon;
                        $businessSettings['brand_color'] = $tenant->industry->color;
                        $tenantIndustry = [
                            'id' => $tenant->industry->id,
                            'name' => $tenant->industry->name,
                            'slug' => $tenant->industry->slug,
                            'icon' => $tenant->industry->icon,
                            'color' => $tenant->industry->color,
                            'business_type' => $tenant->businessType?->name,
                        ];
                    }
                }
            }

            // Fallback: check TenantSetting for tenant industry
            if (!$tenantIndustry) {
                $indSlug = TenantSetting::getByKey('industry_slug', 'real-estate');
                $indName = TenantSetting::getByKey('industry_name', 'Real Estate');
                $ind = \App\Models\Industry::where('slug', $indSlug)->orWhere('name', $indName)->first();
                if ($ind) {
                    $tenantIndustry = [
                        'id' => $ind->id,
                        'name' => $ind->name,
                        'slug' => $ind->slug,
                        'icon' => $ind->icon,
                        'color' => $ind->color,
                        'business_type' => TenantSetting::getByKey('business_type', 'Property & Rentals'),
                    ];
                } else {
                    $tenantIndustry = [
                        'id' => 3,
                        'name' => 'Real Estate',
                        'slug' => 'real-estate',
                        'icon' => '🏠',
                        'color' => 'amber',
                        'business_type' => 'Real Estate & Rentals',
                    ];
                }
            }

            $currentIndustrySlug = $tenantIndustry['slug'] ?? 'real-estate';

            $itemsQuery = NavigationItem::where('is_enabled', true);
            if ($tenantId) {
                $itemsQuery->where(function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)
                      ->orWhereNull('tenant_id');
                })->orderByRaw('tenant_id IS NULL, display_order ASC');
            } else {
                $itemsQuery->whereNull('tenant_id')->orderBy('display_order');
            }

            $items = $itemsQuery->get()
                ->unique('key')
                ->unique('label')
                ->sortBy('display_order');

            // If current industry is NOT matrimonial, filter out matrimonial-specific items
            if ($currentIndustrySlug !== 'matrimonial') {
                $matrimonialKeys = ['biodata', 'verified_members', 'shortlist', 'padhadhikari'];
                $items = $items->reject(function ($item) use ($matrimonialKeys) {
                    return in_array($item->key, $matrimonialKeys) || str_contains($item->route, 'matrimonial') || str_contains($item->route, 'padhadhikari');
                });
            }

            $items = $items->values();

            $customNavList = $items->toArray();
            foreach ($items as $item) {
                $customNav[$item->key] = [
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'route' => $item->route,
                ];
            }

            $businessSettings['business_name'] = TenantSetting::getByKey('business_name', $businessSettings['business_name'], $tenantId);
            $businessSettings['business_icon'] = TenantSetting::getByKey('business_icon', $businessSettings['business_icon'], $tenantId);
            $businessSettings['brand_color'] = TenantSetting::getByKey('brand_color', $businessSettings['brand_color'], $tenantId);

            $storageLimitMb = isset($tenant) ? ($tenant->storage_limit_mb ?: 5120) : 5120;
            $storageUsedMb = isset($tenant) ? ($tenant->storage_used_mb ?: 1250.0) : 1250.0;
            $storageUsedGb = round($storageUsedMb / 1024, 2);
            $storageLimitGb = round($storageLimitMb / 1024, 1);
            $storagePercent = min(100, round(($storageUsedMb / $storageLimitMb) * 100));
        } catch (\Exception $e) {
            // Fallback default settings
            $storageLimitMb = 5120;
            $storageUsedMb = 1250.0;
            $storageUsedGb = 1.22;
            $storageLimitGb = 5.0;
            $storagePercent = 24;
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'current_tenant' => (isset($tenant) && $tenant) ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'subdomain' => $tenant->subdomain,
                'subdomain_url' => $tenant->subdomain_url,
                'database_name' => $tenant->database_name,
                'database_status' => $tenant->database_status,
            ] : null,
            'tenant_industry' => $tenantIndustry,
            'tenant_storage' => [
                'limit_mb' => $storageLimitMb,
                'used_mb' => $storageUsedMb,
                'limit_gb' => $storageLimitGb,
                'used_gb' => $storageUsedGb,
                'used_percent' => $storagePercent,
                'is_over_limit' => $storageUsedMb >= $storageLimitMb,
            ],
            'custom_nav' => $customNav,
            'custom_nav_list' => $customNavList,
            'business_settings' => $businessSettings,
            'crm_plans' => \Illuminate\Support\Facades\DB::table('crm_plans')->where('is_active', true)->get(),
            'cookie_settings' => [
                'consent_given' => $request->cookie('crm_cookie_consent') !== null,
                'theme' => $request->cookie('crm_theme', 'dark'),
                'analytics' => $request->cookie('crm_analytics_consent', 'true') === 'true',
                'marketing' => $request->cookie('crm_marketing_consent', 'false') === 'true',
                'sidebar_collapsed' => $request->cookie('crm_sidebar_collapsed', 'false') === 'true',
            ],
            'session_info' => [
                'driver' => config('session.driver', 'file'),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
