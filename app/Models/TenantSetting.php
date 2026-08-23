<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    public static function getByKey(string $key, ?string $default = null, ?int $tenantId = null): ?string
    {
        $tenantId = $tenantId ?? session('tenant_id') ?? auth()->user()?->tenant_id;

        if ($tenantId) {
            $val = static::where('tenant_id', $tenantId)->where('key', $key)->value('value');
            if ($val !== null) {
                return $val;
            }

            // Fallback to active Tenant model attributes if available
            $tenant = Tenant::with('industry')->find($tenantId);
            if ($tenant) {
                if ($key === 'business_name' && !empty($tenant->name)) {
                    return $tenant->name;
                }
                if ($key === 'business_icon' && $tenant->industry) {
                    return $tenant->industry->icon ?: $default;
                }
                if ($key === 'brand_color' && $tenant->industry) {
                    return $tenant->industry->color ?: $default;
                }
                if ($key === 'industry_name' && $tenant->industry) {
                    return $tenant->industry->name ?: $default;
                }
                if ($key === 'industry_slug' && $tenant->industry) {
                    return $tenant->industry->slug ?: $default;
                }
            }
        }

        // Check for general fallback setting without tenant_id (only for non-brand specific keys)
        if (!in_array($key, ['business_name', 'business_icon'])) {
            $globalVal = static::whereNull('tenant_id')->where('key', $key)->value('value');
            if ($globalVal !== null) {
                return $globalVal;
            }
        }

        return $default;
    }

    public static function setByKey(string $key, ?string $value, ?int $tenantId = null): void
    {
        $tenantId = $tenantId ?? session('tenant_id') ?? auth()->user()?->tenant_id;

        static::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => $value]
        );
    }
}
