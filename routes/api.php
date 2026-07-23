<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuruCetakController;
use App\Http\Controllers\Api\GuruCalendarEventController;
use App\Http\Controllers\Api\GuruDashboardController;
use App\Http\Controllers\Api\GuruElapkinController;
use App\Http\Controllers\Api\GuruJurnalController;
use App\Http\Controllers\Api\GuruPengumumanController;
use App\Http\Controllers\Api\GuruProfileController;
use App\Http\Controllers\Api\AppUpdateController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'simpatisans-api',
        'time' => now()->toIso8601String(),
    ]);
});

Route::get('/app-update/{platform}', [AppUpdateController::class, 'show']);

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
    Route::get('/pengumuman', [GuruPengumumanController::class, 'index']);
    Route::get('/calendar-events', [GuruCalendarEventController::class, 'index']);
    Route::get('/cetak/jadwal-pelajaran', [GuruCetakController::class, 'jadwalPelajaran']);
    Route::get('/cetak/lampiran-sk', [GuruCetakController::class, 'lampiranSk']);

    Route::get('/jurnal', [GuruJurnalController::class, 'index']);
    Route::post('/jurnal', [GuruJurnalController::class, 'store']);
    Route::get('/jurnal/reminder-hari-ini', [GuruJurnalController::class, 'reminderHariIni']);
    Route::get('/jurnal/cetak', [GuruJurnalController::class, 'cetakSemua']);
    Route::get('/jurnal/{kelas}/cetak', [GuruJurnalController::class, 'cetak']);
    Route::get('/jurnal/{kelas}/jam-options', [GuruJurnalController::class, 'jamOptions']);
    Route::get('/jurnal/{kelas}', [GuruJurnalController::class, 'show']);
    Route::put('/jurnal/{jurnal}', [GuruJurnalController::class, 'update']);
    Route::delete('/jurnal/{jurnal}', [GuruJurnalController::class, 'destroy']);

    Route::get('/elapkin-sso', [GuruElapkinController::class, 'ssoToken']);
    Route::post('/elapkin-bridge', [GuruElapkinController::class, 'bridgeSession']);
    Route::get('/hari-libur', [GuruElapkinController::class, 'hariLibur']);
    Route::put('/profile/biodata', [GuruProfileController::class, 'updateBiodata']);
    Route::put('/profile/kontak', [GuruProfileController::class, 'updateKontak']);
    Route::post('/profile/photo', [GuruProfileController::class, 'updatePhoto']);
});
