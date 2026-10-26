<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventarisApiController;
use App\Http\Controllers\Api\KontrakApiController;
use App\Http\Controllers\Api\PublicScanApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute REST API untuk Sistem Informasi Inventaris Sewa Beli.
| Seluruh rute memiliki prefix "/api" dan middleware group "api".
|
*/

// 1. Endpoint Pemeriksaan Kondisi Layanan API (Publik & Bebas Rahasia)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Sistem Informasi Inventaris Sewa Beli API is healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// 2. Endpoint Scan Publik (Hanya Field Whitelist, Bebas Data Finansial / Biaya Sewa)
Route::get('/scan/{kode}', [PublicScanApiController::class, 'show'])->name('api.scan');
Route::get('/public/scan/{kode}', [PublicScanApiController::class, 'show'])->name('api.public.scan');

// 3. Endpoint Stateful SPA (Menggunakan Middleware Sesi Bawaan 'web' & CSRF)
Route::middleware('web')->group(function () {
    // Inisiasi Cookie Sesi dan CSRF Token untuk Frontend SPA (React)
    Route::get('/csrf-cookie', [\App\Http\Controllers\Api\AuthApiController::class, 'csrfCookie'])->name('api.csrf-cookie');

    // Login Admin (Publik tetapi Terlindungi CSRF & Memulai Sesi)
    Route::post('/login', [\App\Http\Controllers\Api\AuthApiController::class, 'login'])->name('api.login');

    // Endpoint Internal Admin (Wajib Memiliki Sesi Aktif 'auth' dan Akun Resmi 'IT' / 'Finance')
    Route::middleware(['auth', \App\Http\Middleware\EnsureOfficialAccount::class])->group(function () {
        // Pemeriksaan Pengguna Aktif & Logout
        Route::get('/me', [\App\Http\Controllers\Api\AuthApiController::class, 'me'])->name('api.me');
        Route::post('/logout', [\App\Http\Controllers\Api\AuthApiController::class, 'logout'])->name('api.logout');

        // Dashboard Stats & Export API
        Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'stats'])->name('stats');
            Route::get('/export/pdf', [\App\Http\Controllers\Api\DashboardApiController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [\App\Http\Controllers\Api\DashboardApiController::class, 'exportExcel'])->name('export.excel');
        });

        // API Inventaris (Daftar, Detail, Tambah, Ubah, Hapus, Export)
        Route::prefix('inventaris')->name('api.inventaris.')->group(function () {
            Route::get('/', [InventarisApiController::class, 'index'])->name('index');
            Route::get('/export/pdf', [InventarisApiController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [InventarisApiController::class, 'exportExcel'])->name('export.excel');
            Route::get('/{id}', [InventarisApiController::class, 'show'])->name('show');
            Route::post('/', [InventarisApiController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [InventarisApiController::class, 'update'])->name('update');
            Route::delete('/{id}', [InventarisApiController::class, 'destroy'])->name('destroy');
        });

        // API Kontrak (Daftar, Detail, Tambah, Ubah, Hapus, Export — Termasuk Data Keuangan & Biaya Sewa)
        Route::prefix('kontrak')->name('api.kontrak.')->group(function () {
            Route::get('/', [KontrakApiController::class, 'index'])->name('index');
            Route::get('/export/pdf', [KontrakApiController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export/excel', [KontrakApiController::class, 'exportExcel'])->name('export.excel');
            Route::get('/{id}', [KontrakApiController::class, 'show'])->name('show');
            Route::post('/', [KontrakApiController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '/{id}', [KontrakApiController::class, 'update'])->name('update');
            Route::delete('/{id}', [KontrakApiController::class, 'destroy'])->name('destroy');
        });

        // Barcode PNG Download API
        Route::get('/barcode/{kode}/download', [\App\Http\Controllers\BarcodeController::class, 'download'])->name('api.barcode.download');
    });
});

