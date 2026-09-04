<?php

use App\Http\Middleware\ActiveRoleMiddleware;
use App\Http\Middleware\AuthenticateTalimApi;
use App\Http\Middleware\EnforceFirstLogin;
use App\Http\Middleware\ProtectActiveSemester;
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
            'active_role' => ActiveRoleMiddleware::class,
            'first_login' => EnforceFirstLogin::class,
            'semester_unlocked' => ProtectActiveSemester::class,
            'auth.talim' => AuthenticateTalimApi::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API mobile Ta'lim: selalu JSON, hindari error view yang butuh facade View
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
