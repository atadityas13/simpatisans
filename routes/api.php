<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuruDashboardController;
use App\Http\Controllers\Api\GuruElapkinController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('guru')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index']);
    Route::get('/jadwal', [GuruDashboardController::class, 'jadwal']);
    Route::get('/elapkin-sso', [GuruElapkinController::class, 'ssoToken']);
});
