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

            $items = NavigationItem::where('is_enabled', true)->orderBy('display_order')->get();
            $customNavList = $items->toArray();
            foreach ($items as $item) {
                $customNav[$item->key] = [
                    'label' => $item->label,
                    'icon' => $item->icon,
                    'route' => $item->route,
                ];
            }

            $businessSettings['business_name'] = TenantSetting::getByKey('business_name', $businessSettings['business_name']);
            $businessSettings['business_icon'] = TenantSetting::getByKey('business_icon', $businessSettings['business_icon']);
            $businessSettings['brand_color'] = TenantSetting::getByKey('brand_color', $businessSettings['brand_color']);

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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
