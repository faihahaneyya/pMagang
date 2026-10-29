<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventarisApiController;
use App\Http\Controllers\Api\KontrakApiController;
use App\Http\Controllers\Api\VendorApiController;
use App\Http\Controllers\Api\ContractItemApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\PublicScanApiController;

/*
|--------------------------------------------------------------------------
| API Routes — Sistem Informasi Inventaris Sewa Beli
|--------------------------------------------------------------------------
|
| RBAC:
|   - Publik: /scan/{kode}, /health
|   - IT + KEUANGAN: dashboard, inventaris (read), kontrak, vendor
|   - IT only: inventaris (write/delete), user management, vendor delete
|   - USER (setelah login): hanya /scan/{kode} via PublicScanApiController
|
*/

// 1. Health Check (Publik)
Route::get('/health', function () {
    return response()->json([
        'status'    => 'ok',
        'message'   => 'Sistem Informasi Inventaris Sewa Beli API is healthy',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// 2. Scan Publik (Tanpa Auth — untuk USER scan barcode)
Route::get('/scan/{kode}', [PublicScanApiController::class, 'show'])->name('api.scan');
Route::get('/public/scan/{kode}', [PublicScanApiController::class, 'show'])->name('api.public.scan');

// 3. Endpoint Stateful SPA (Menggunakan Middleware Sesi 'web' & CSRF)
Route::middleware('web')->group(function () {
    // CSRF Cookie
    Route::get('/csrf-cookie', [\App\Http\Controllers\Api\AuthApiController::class, 'csrfCookie'])->name('api.csrf-cookie');

    // Login (Publik — semua role)
    Route::post('/login', [\App\Http\Controllers\Api\AuthApiController::class, 'login'])->name('api.login');

    // ===== ENDPOINT YANG MEMBUTUHKAN AUTH =====
    Route::middleware('auth')->group(function () {

        // Me & Logout (semua role yang login)
        Route::get('/me', [\App\Http\Controllers\Api\AuthApiController::class, 'me'])->name('api.me');
        Route::post('/logout', [\App\Http\Controllers\Api\AuthApiController::class, 'logout'])->name('api.logout');

        // ===== IT + KEUANGAN ONLY (Admin Routes) =====
        Route::middleware(\App\Http\Middleware\EnsureOfficialAccount::class)->group(function () {

            // Dashboard Stats & Export
            Route::prefix('dashboard')->name('api.dashboard.')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'stats'])->name('stats');
                Route::get('/export/pdf', [\App\Http\Controllers\Api\DashboardApiController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/export/excel', [\App\Http\Controllers\Api\DashboardApiController::class, 'exportExcel'])->name('export.excel');
            });

            // API Vendor (IT + KEUANGAN: CRUD Lengkap dengan Proteksi Relasi Kontrak)
            Route::prefix('vendor')->name('api.vendor.')->group(function () {
                Route::get('/', [VendorApiController::class, 'index'])->name('index');
                Route::get('/all', [VendorApiController::class, 'all'])->name('all');
                Route::get('/{id}', [VendorApiController::class, 'show'])->name('show');
                Route::post('/', [VendorApiController::class, 'store'])->name('store');
                Route::match(['put', 'patch'], '/{id}', [VendorApiController::class, 'update'])->name('update');
                Route::delete('/{id}', [VendorApiController::class, 'destroy'])->name('destroy');
            });

            // API Inventaris (Read: IT + KEUANGAN; Write/Delete: IT only)
            Route::prefix('inventaris')->name('api.inventaris.')->group(function () {
                Route::get('/', [InventarisApiController::class, 'index'])->name('index');
                Route::get('/export/pdf', [InventarisApiController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/export/excel', [InventarisApiController::class, 'exportExcel'])->name('export.excel');
                Route::get('/{id}', [InventarisApiController::class, 'show'])->name('show');
                // Operasi write & delete: IT only
                Route::middleware(\App\Http\Middleware\RoleMiddleware::class . ':IT')->group(function () {
                    Route::post('/', [InventarisApiController::class, 'store'])->name('store');
                    Route::match(['put', 'patch'], '/{id}', [InventarisApiController::class, 'update'])->name('update');
                    Route::delete('/{id}', [InventarisApiController::class, 'destroy'])->name('destroy');
                    Route::delete('/{id}/photos/{photoId}', [InventarisApiController::class, 'destroyPhoto'])->name('photos.destroy');
                });
            });

            // API Kontrak (IT + KEUANGAN: CRUD Lengkap dengan Proteksi Relasi Items)
            Route::prefix('kontrak')->name('api.kontrak.')->group(function () {
                Route::get('/', [KontrakApiController::class, 'index'])->name('index');
                Route::get('/export/pdf', [KontrakApiController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/export/excel', [KontrakApiController::class, 'exportExcel'])->name('export.excel');
                Route::get('/{id}', [KontrakApiController::class, 'show'])->name('show');
                Route::post('/', [KontrakApiController::class, 'store'])->name('store');
                Route::match(['put', 'patch'], '/{id}', [KontrakApiController::class, 'update'])->name('update');
                Route::delete('/{id}', [KontrakApiController::class, 'destroy'])->name('destroy');

                // Contract Items (relasi kontrak-inventaris)
                Route::get('/{id}/items', [ContractItemApiController::class, 'index'])->name('items.index');
                // Manage items: IT only
                Route::middleware(\App\Http\Middleware\RoleMiddleware::class . ':IT')->group(function () {
                    Route::post('/{id}/items', [ContractItemApiController::class, 'store'])->name('items.store');
                    Route::delete('/{id}/items/{itemId}', [ContractItemApiController::class, 'destroy'])->name('items.destroy');
                });
            });

            // Barcode PNG Download
            Route::get('/barcode/{kode}/download', [\App\Http\Controllers\BarcodeController::class, 'download'])->name('api.barcode.download');

            // User Management: IT only
            Route::middleware(\App\Http\Middleware\RoleMiddleware::class . ':IT')
                ->prefix('users')
                ->name('api.users.')
                ->group(function () {
                    Route::get('/', [UserApiController::class, 'index'])->name('index');
                    Route::get('/{id}', [UserApiController::class, 'show'])->name('show');
                    Route::post('/', [UserApiController::class, 'store'])->name('store');
                    Route::match(['put', 'patch'], '/{id}', [UserApiController::class, 'update'])->name('update');
                    Route::delete('/{id}', [UserApiController::class, 'destroy'])->name('destroy');
                });
        });
    });
});
