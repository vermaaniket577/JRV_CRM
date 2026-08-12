<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Redirect authenticated users to onboarding if their tenant hasn't completed setup.
     * Allows access to /onboarding, /login, /register, and /logout routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-authenticated users
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip for these routes
        $allowedPaths = ['/onboarding', '/login', '/register', '/logout', '/api/'];
        foreach ($allowedPaths as $path) {
            if (str_starts_with($request->getPathInfo(), $path)) {
                return $next($request);
            }
        }

        $user = auth()->user();

        if ($user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);

            if ($tenant && !$tenant->onboarding_completed) {
                return redirect('/onboarding');
            }
        }

        return $next($request);
    }
}
