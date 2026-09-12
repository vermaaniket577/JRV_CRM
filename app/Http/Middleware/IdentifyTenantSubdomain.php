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
        $reserved = ['www', 'admin', 'api', 'app', 'localhost', '127.0.0.1', 'mail', 'master'];
        $isSubdomain = $subdomain && !in_array(strtolower($subdomain), $reserved);

        if ($isSubdomain) {
            // Subdomain is a dedicated User Domain / Tenant Workspace
            $tenant = Tenant::with(['industry', 'businessType'])
                ->where('subdomain', $subdomain)
                ->orWhere('slug', $subdomain)
                ->first();

            if (!$tenant) {
                abort(404, "Tenant workspace '{$subdomain}' does not exist.");
            }

            session(['tenant_id' => $tenant->id]);
            app()->instance('current_tenant', $tenant);
            app()->instance('is_tenant_subdomain', true);
            app()->instance('is_master_admin_domain', false);

            // Dynamically register connection to tenant dedicated database
            if ($tenant->database_name && $tenant->database_status === 'active') {
                $dbService = app(TenantDatabaseService::class);
                $dbService->registerDynamicConnection($tenant->database_name);
            }
        } else {
            // Main domain is the Master Admin Domain
            app()->instance('is_tenant_subdomain', false);
            app()->instance('is_master_admin_domain', true);

            // Optional explicit tenant preview via ?tenant=... for Master Admin
            if ($queryTenant = $request->query('tenant')) {
                $tenant = Tenant::with(['industry', 'businessType'])
                    ->where('subdomain', $queryTenant)
                    ->orWhere('slug', $queryTenant)
                    ->orWhere('id', is_numeric($queryTenant) ? $queryTenant : 0)
                    ->first();
                if ($tenant) {
                    session(['tenant_id' => $tenant->id]);
                    app()->instance('current_tenant', $tenant);
                }
            } else {
                $user = $request->user();
                if ($user && !$user->is_super_admin && $user->tenant_id) {
                    $tenant = Tenant::with(['industry', 'businessType'])->find($user->tenant_id);
                    if ($tenant) {
                        session(['tenant_id' => $tenant->id]);
                        app()->instance('current_tenant', $tenant);
                    }
                } elseif ($user && $user->is_super_admin) {
                    // Super admin operating on main domain - clear tenant_id to view master level
                    session()->forget('tenant_id');
                }
            }
        }

        // Auto-login transfer token processing across subdomains
        if ($authToken = $request->query('auth_token')) {
            $userId = \Illuminate\Support\Facades\Cache::pull("subdomain_auth_{$authToken}");
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    \Illuminate\Support\Facades\Auth::login($user);
                    if ($user->tenant_id && !$tenant) {
                        $tenant = Tenant::with(['industry', 'businessType'])->find($user->tenant_id);
                        if ($tenant) {
                            session(['tenant_id' => $tenant->id]);
                            app()->instance('current_tenant', $tenant);
                        }
                    }
                    
                    $cleanUrl = $request->fullUrlWithoutQuery(['auth_token']);
                    return redirect()->to($cleanUrl);
                }
            }
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host string.
     */
    private function extractSubdomain(string $host): ?string
    {
        // Don't treat IP addresses as subdomains (e.g. 127.0.0.1)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

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
