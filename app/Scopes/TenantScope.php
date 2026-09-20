<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply global tenant scope filter to all queries automatically.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $table = $model->getTable();

        // 1. Super Admin global view bypass (only when on global root domain, NOT on a tenant subdomain)
        if (auth()->check() && auth()->user()->is_super_admin && !(app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain'))) {
            if (session()->has('tenant_id')) {
                $builder->where($table . '.tenant_id', session('tenant_id'));
            }
            return;
        }

        // 2. Resolve tenant ID from all secure contexts
        $tenantId = null;

        if (app()->bound('current_tenant') && app('current_tenant')) {
            $tenantId = app('current_tenant')->id;
        } elseif (session()->has('tenant_id')) {
            $tenantId = session('tenant_id');
        } elseif (auth()->check() && auth()->user()->tenant_id) {
            $tenantId = auth()->user()->tenant_id;
        } else {
            // Check request host for subdomain
            try {
                $request = request();
                if ($request) {
                    $host = $request->getHost();
                    $parts = explode('.', $host);
                    if (count($parts) >= 2 && !in_array(strtolower($parts[0]), ['localhost', '127', 'www', 'admin', 'api'])) {
                        $tenant = \App\Models\Tenant::where('subdomain', $parts[0])->orWhere('slug', $parts[0])->first();
                        if ($tenant) {
                            $tenantId = $tenant->id;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 3. Strict isolation enforcement
        if ($tenantId) {
            $builder->where($table . '.tenant_id', $tenantId);
        } else {
            // ZERO-LEAK PRINCIPLE: If no valid tenant context exists, block all records
            $builder->whereRaw('1 = 0');
        }
    }
}
