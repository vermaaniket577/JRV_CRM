<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenant
{
    /**
     * Handle an incoming request to validate tenant access and check subscription status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_super_admin) {
            return $next($request);
        }

        if (!$user || !$user->tenant_id) {
            abort(403, 'Unauthorized. User does not belong to a valid tenant organization.');
        }

        $tenant = $user->tenant;

        if (!$tenant || $tenant->status !== 'active') {
            abort(403, 'Your organization account is currently suspended or inactive.');
        }

        // Store tenant ID in session for query isolation
        session(['tenant_id' => $tenant->id]);

        return $next($request);
    }
}
