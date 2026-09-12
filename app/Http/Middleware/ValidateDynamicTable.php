<?php

namespace App\Http\Middleware;

use App\Models\CrmTable;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that the {table} route parameter matches an active table
 * in the tenant's CRM metadata. Prevents SQL injection via table names.
 */
class ValidateDynamicTable
{
    public function handle(Request $request, Closure $next): Response
    {
        $tableName = $request->route('table');

        if (!$tableName) {
            return $next($request);
        }

        // 1. Sanitize: only allow alphanumeric + underscores
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/', $tableName)) {
            abort(404, 'Invalid table name.');
        }

        // 2. Resolve tenant
        $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
        if (!$tenantId) {
            abort(403, 'Tenant not found.');
        }

        // 3. Whitelist check: table must exist in crm_tables metadata
        $crmTable = CrmTable::where('tenant_id', $tenantId)
            ->where('table_name', $tableName)
            ->where('is_active', true)
            ->first();

        if (!$crmTable) {
            abort(404, "Table '{$tableName}' not found in your CRM database.");
        }

        // 4. Share the validated table metadata with the request
        $request->attributes->set('crm_table', $crmTable);

        return $next($request);
    }
}
