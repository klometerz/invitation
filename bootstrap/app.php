<?php

use App\Http\Middleware\EnsureTenantSelected;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ResolveAdminTenant;
use App\Http\Middleware\ResolveInvitation;
use App\Http\Middleware\ResolveInvitationFromGuestCode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // Sekarang pintu API udah aktif
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Cukup satu blok middleware buat semua settingan
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Tambahkan ini:
        $middleware->trustProxies(at: '*');

        // Multi-tenant middleware aliases (P1-T4).
        $middleware->alias([
            'tenant.public' => ResolveInvitation::class,
            'tenant.guestcode' => ResolveInvitationFromGuestCode::class,
            'tenant.admin' => ResolveAdminTenant::class,
            'tenant.selected' => EnsureTenantSelected::class,
            'role' => EnsureUserHasRole::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create(); // create() SELALU di paling bawah
