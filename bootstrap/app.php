<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active_role' => \App\Http\Middleware\ActiveRoleMiddleware::class,
            'first_login' => \App\Http\Middleware\EnforceFirstLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API mobile Ta'lim: selalu JSON, hindari error view yang butuh facade View
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
