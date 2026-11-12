<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataPendaftarController;
use App\Http\Controllers\ImportDataPendaftarController;
use App\Http\Controllers\PengaturanSpkController;
use App\Http\Controllers\PerhitunganController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Grup Analisis (Admin & Pimpinan)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/ekspor', [DashboardController::class, 'exportRekomendasi'])->name('dashboard.export');

    Route::get('/perhitungan', [PerhitunganController::class, 'index'])->name('perhitungan.index');

    // Grup Data Historis & Import Excel (Khusus Admin — diotorisasi via Policy)
    Route::get('/data-historis/ekspor', [DataPendaftarController::class, 'export'])->name('data-historis.export');
    Route::resource('data-historis', DataPendaftarController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['data-historis' => 'data_pendaftar']);

    Route::prefix('import-excel')->name('import-excel.')->group(function (): void {
        Route::get('/', [ImportDataPendaftarController::class, 'index'])->name('index');
        Route::get('/template', [ImportDataPendaftarController::class, 'downloadTemplate'])->name('template');
        Route::post('/pratinjau', [ImportDataPendaftarController::class, 'preview'])->name('preview');
        Route::post('/konfirmasi', [ImportDataPendaftarController::class, 'confirm'])->name('confirm');
        Route::post('/batal', [ImportDataPendaftarController::class, 'cancel'])->name('cancel');
    });

    // Grup Pengaturan Parameter SPK (Khusus Admin — diotorisasi via Policy)
    Route::prefix('pengaturan-spk')->name('pengaturan-spk.')->group(function (): void {
        Route::get('/', [PengaturanSpkController::class, 'index'])->name('index');
        Route::put('/', [PengaturanSpkController::class, 'update'])->name('update');
        Route::post('/reset', [PengaturanSpkController::class, 'resetDefault'])->name('reset');
    });
});
