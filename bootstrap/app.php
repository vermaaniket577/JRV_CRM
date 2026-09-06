<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'api/v1/*',
            'api/v1/integration/*',
            'embed/*',
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenantSubdomain::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\EnsureOnboardingCompleted::class,
        ]);
        $middleware->alias([
            'tenant' => \App\Http\Middleware\EnsureValidTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
