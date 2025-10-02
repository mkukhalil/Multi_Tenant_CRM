<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 🔹 Global middleware stack (runs on every request)
        $middleware->use([
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // 🔹 Add to the "web" group
        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // 🔹 Middleware aliases
        $middleware->alias([
            'auth'              => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic'        => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session'      => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers'     => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can'               => \Illuminate\Auth\Middleware\Authorize::class,
            'guest'             => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm'  => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive'      => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed'            => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle'          => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified'          => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

            // 🔹 Your custom middleware
            'tenant'            => TenantMiddleware::class,
            'role' => RoleMiddleware::class,
        'permission' => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
