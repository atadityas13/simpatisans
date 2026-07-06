<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\MapelController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\TugasTambahanController;
use App\Http\Controllers\PembagianTugasController;
use App\Http\Controllers\JadwalController;

use App\Http\Controllers\SemesterController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\AuthController;

// Auth Routes (Guest)
Route::group(['middleware' => 'guest'], function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.post');
    
    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password/verify', [AuthController::class, 'verifyForgotPassword'])->name('password.verify');
    Route::post('forgot-password/request', [AuthController::class, 'requestReset'])->name('password.reset.request');
});

// Auth Routes (Protected)
Route::group(['middleware' => 'auth'], function () {
    // First Login Setup (Allowed before enforcement)
    Route::get('first-login', [AuthController::class, 'showFirstLogin'])->name('first-login');
    Route::post('first-login', [AuthController::class, 'completeFirstLogin'])->name('first-login.post');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Enforced Routes (Setup must be complete)
    Route::group(['middleware' => 'first_login'], function () {
        // Profile Routes
        Route::get('profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::post('profile/photo', [App\Http\Controllers\ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
        Route::post('profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Role Selection (Splash)
        Route::get('select-role', [AuthController::class, 'showSelectRole'])->name('auth.select-role');
        Route::post('select-role', [AuthController::class, 'selectRole'])->name('select-role.post');
        Route::get('switch-role/{role}', [AuthController::class, 'switchRole'])->name('auth.switch-role');

        // ADMIN CONTEXT ROUTES
        Route::group(['middleware' => 'active_role:admin'], function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('jadwal', [JadwalController::class, 'index'])->name('jadwal.index');
            Route::post('jadwal/generate', [JadwalController::class, 'generate'])->name('jadwal.generate');
            Route::post('jadwal/update-slot', [JadwalController::class, 'updateSlot'])->name('jadwal.update-slot');
            Route::post('jadwal/update-slots-batch', [JadwalController::class, 'updateSlotsBatch'])->name('jadwal.update-slots-batch');
            Route::delete('jadwal/clear', [JadwalController::class, 'clear'])->name('jadwal.clear');
            Route::post('jadwal/toggle-constraint', [JadwalController::class, 'toggleConstraint'])->name('jadwal.toggle-constraint');

            Route::get('cetak', [App\Http\Controllers\CetakController::class, 'index'])->name('cetak.index');
            Route::get('cetak/jadwal-pelajaran', [App\Http\Controllers\CetakController::class, 'jadwalPelajaran'])->name('cetak.jadwal-pelajaran');
            Route::get('cetak/jadwal-besar', [App\Http\Controllers\CetakController::class, 'jadwalBesar'])->name('cetak.jadwal-besar');
            Route::get('cetak/jadwal-piket', [App\Http\Controllers\CetakController::class, 'jadwalPiket'])->name('cetak.jadwal-piket');
            Route::get('cetak/lampiran-sk', [App\Http\Controllers\CetakController::class, 'lampiranSk'])->name('cetak.lampiran-sk');
            Route::post('cetak/presets', [App\Http\Controllers\CetakController::class, 'storePresets'])->name('cetak.presets.store');

            Route::get('pembagian-tugas', [PembagianTugasController::class, 'index'])->name('pembagian.index');
            Route::get('pembagian-tugas/guru/{guru}', [PembagianTugasController::class, 'show'])->name('pembagian.show');
            Route::post('pembagian-tugas/guru/{guru}/kbm', [PembagianTugasController::class, 'storeKbm'])->name('pembagian.kbm.store');
            Route::delete('pembagian-tugas/kbm/{beban}', [PembagianTugasController::class, 'destroyKbm'])->name('pembagian.kbm.destroy');
            Route::delete('pembagian-tugas/guru/{guru}/kbm-clear', [PembagianTugasController::class, 'clearKbm'])->name('pembagian.kbm.clear');

            Route::post('pembagian-tugas/guru/{guru}/non-satminkal', [PembagianTugasController::class, 'storeNonSatminkal'])->name('pembagian.non-satminkal.store');
            Route::delete('pembagian-tugas/non-satminkal/{beban}', [PembagianTugasController::class, 'destroyNonSatminkal'])->name('pembagian.non-satminkal.destroy');
            Route::post('pembagian-tugas/guru/{guru}/tugas', [PembagianTugasController::class, 'storeTugas'])->name('pembagian.tugas.store');
            Route::delete('pembagian-tugas/guru/{guru}/tugas/{tugas}', [PembagianTugasController::class, 'destroyTugas'])->name('pembagian.tugas.destroy');

            Route::resource('master/guru', GuruController::class);
            Route::resource('master/mapel', MapelController::class);
            Route::resource('master/kelas', KelasController::class);
            Route::resource('master/tugas-tambahan', TugasTambahanController::class);

            Route::resource('pengaturan/semester', SemesterController::class);
            Route::post('pengaturan/semester/{semester}/activate', [SemesterController::class, 'activate'])->name('semester.activate');

            // Admin roles and account management
            Route::resource('pengaturan/admin', AdminController::class)->except(['create', 'show', 'edit']);
            
            // Database Management
            Route::get('pengaturan/database', [App\Http\Controllers\DatabaseController::class, 'index'])->name('database.index');
            Route::post('pengaturan/database/truncate', [App\Http\Controllers\DatabaseController::class, 'truncate'])->name('database.truncate');

            // Teacher account management (Pengguna)
            Route::get('config/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
            Route::post('config/pengguna/generate/{guru}', [PenggunaController::class, 'generate'])->name('pengguna.generate');
            Route::patch('config/pengguna/{user}/toggle', [PenggunaController::class, 'toggleStatus'])->name('pengguna.toggle');
            Route::post('config/pengguna/{user}/reset', [PenggunaController::class, 'resetPassword'])->name('pengguna.reset');
            Route::post('config/pengguna/{user}/photo', [PenggunaController::class, 'updatePhoto'])->name('pengguna.photo');
        });

        // GURU CONTEXT ROUTES
        Route::group(['middleware' => 'active_role:guru'], function () {
            Route::get('/guru', function () {
                return view('guru.dashboard');
            })->name('guru.dashboard');
        });
    });
});
