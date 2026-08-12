<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMasterAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->is_super_admin) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated Master Admin access.'], 403);
            }

            return redirect()->route('admin.login')->with('error', 'Master Admin login required to access Admin Control Panel.');
        }

        return $next($request);
    }
}
