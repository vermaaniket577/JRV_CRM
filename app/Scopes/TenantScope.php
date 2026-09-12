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

        // 1. If currently in a dedicated tenant subdomain, strictly filter to current tenant
        if (app()->bound('is_tenant_subdomain') && app('is_tenant_subdomain')) {
            $currentTenant = app()->bound('current_tenant') ? app('current_tenant') : null;
            $tenantId = $currentTenant?->id ?? session('tenant_id');
            if ($tenantId) {
                $builder->where($table . '.tenant_id', $tenantId);
            } else {
                $builder->whereRaw('1 = 0');
            }
            return;
        }

        // 2. Fallback to session or authenticated user
        if (session()->has('tenant_id')) {
            $builder->where($table . '.tenant_id', session('tenant_id'));
        } elseif (auth()->check() && auth()->user()->tenant_id && !auth()->user()->is_super_admin) {
            $builder->where($table . '.tenant_id', auth()->user()->tenant_id);
        }
    }
}
