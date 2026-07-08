<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuruCetakController;
use App\Http\Controllers\Api\GuruDashboardController;
use App\Http\Controllers\Api\GuruElapkinController;
use App\Http\Controllers\Api\GuruProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'simpatisans-api',
        'time' => now()->toIso8601String(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/password', [AuthController::class, 'updatePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('guru')->group(function () {
    Route::get('/dashboard', [GuruDashboardController::class, 'index']);
    Route::get('/jadwal', [GuruDashboardController::class, 'jadwal']);
    Route::get('/cetak/jadwal-pelajaran', [GuruCetakController::class, 'jadwalPelajaran']);
    Route::get('/cetak/lampiran-sk', [GuruCetakController::class, 'lampiranSk']);
    Route::get('/elapkin-sso', [GuruElapkinController::class, 'ssoToken']);
    Route::post('/elapkin-bridge', [GuruElapkinController::class, 'bridgeSession']);
    Route::put('/profile/biodata', [GuruProfileController::class, 'updateBiodata']);
    Route::put('/profile/kontak', [GuruProfileController::class, 'updateKontak']);
    Route::post('/profile/photo', [GuruProfileController::class, 'updatePhoto']);
});
