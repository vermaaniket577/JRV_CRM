<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDatabaseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantSubdomain
{
    /**
     * Handle incoming request by resolving tenant from subdomain or session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;
        $host = $request->getHost();

        // 1. Extract subdomain from host
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain && !in_array($subdomain, ['www', 'admin', 'api', 'app', 'localhost', '127.0.0.1'])) {
            $tenant = Tenant::with(['industry', 'businessType'])
                ->where('subdomain', $subdomain)
                ->orWhere('slug', $subdomain)
                ->first();
        }

        // 2. Fallback to query parameter ?tenant=...
        if (!$tenant && ($queryTenant = $request->query('tenant'))) {
            $tenant = Tenant::with(['industry', 'businessType'])
                ->where('subdomain', $queryTenant)
                ->orWhere('slug', $queryTenant)
                ->orWhere('id', is_numeric($queryTenant) ? $queryTenant : 0)
                ->first();
        }

        // 3. Fallback to user session or authenticated user
        if (!$tenant) {
            $tenantId = session('tenant_id') ?? $request->user()?->tenant_id;
            if ($tenantId) {
                $tenant = Tenant::with(['industry', 'businessType'])->find($tenantId);
            }
        }

        // Default to active tenant (e.g. 7 Admissions Dekho) if none resolved
        if (!$tenant) {
            $tenant = Tenant::with(['industry', 'businessType'])->find(7) ?? Tenant::first();
        }

        if ($tenant) {
            session(['tenant_id' => $tenant->id]);
            app()->instance('current_tenant', $tenant);

            // Dynamically register connection to tenant dedicated database
            if ($tenant->database_name && $tenant->database_status === 'active') {
                $dbService = app(TenantDatabaseService::class);
                $dbService->registerDynamicConnection($tenant->database_name);
            }
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host string.
     */
    private function extractSubdomain(string $host): ?string
    {
        // Handle localhost with subdomain e.g. "admissionsdekho.localhost"
        if (str_ends_with($host, '.localhost')) {
            return explode('.', $host)[0];
        }

        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
