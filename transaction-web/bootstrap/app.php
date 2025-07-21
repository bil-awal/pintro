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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'web.auth' => \App\Http\Middleware\WebAuthMiddleware::class,
            // 'filament.user.auth' => \App\Http\Middleware\FilamentUserAuth::class, // TEMPORARY DISABLED
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.guest' => \App\Http\Middleware\AdminGuest::class,
            'admin.token' => \App\Http\Middleware\ValidateAdminToken::class,
            'admin.role' => \App\Http\Middleware\AdminRole::class,
            'admin.log' => \App\Http\Middleware\LogAdminActivity::class,
        ]);
        
        // Disable CSP for development
        $middleware->web([
            \App\Http\Middleware\DisableCSP::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
