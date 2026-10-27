<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\KontrakController;
use App\Http\Controllers\BarcodeController;

/*
|--------------------------------------------------------------------------
| Public Route — Accessible without login (for barcode scanning)
|--------------------------------------------------------------------------
*/
Route::get('/inventaris/scan/{kode}', [InventarisController::class, 'scan'])
    ->name('inventaris.scan');

Route::get('/inventaris/{kode}', [InventarisController::class, 'scan'])
    ->name('inventaris.show')
    ->where('kode', '[A-Za-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Halaman Welcome Default Laravel (Visual Resmi Laravel) & API Support
Route::get('/', function (\Illuminate\Http\Request $request) {
    if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json([
            'status' => 'online',
            'service' => 'Sistem Informasi Inventaris Sewa Beli PT Banyan Koalindo Lestari - Backend API',
            'frontend_url' => config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')),
            'api_status' => 'active',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    return view('welcome');
})->name('dashboard');

// Barcode & Print (Akses cetak label via browser dari frontend SPA)
Route::prefix('admin/barcode')->name('barcode.')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('index');
    Route::get('/{kode}/download', [BarcodeController::class, 'download'])->name('download');
    Route::get('/{kode}/print', [BarcodeController::class, 'print'])->name('print');
    Route::match(['get', 'post'], '/print-all', [BarcodeController::class, 'printAll'])->name('print-all');
});

// Admin Legacy Routes (Dialihkan ke SPA React / Dipertahankan untuk Ekspor File)
Route::middleware(['auth', \App\Http\Middleware\EnsureOfficialAccount::class])->group(function () {
    // Dashboard Exports (PDF & Excel)
    Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.export.pdf');
    Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');

    // Inventaris
    Route::prefix('admin/inventaris')->name('inventaris.')->group(function () {
        Route::get('/', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/inventaris");
        })->name('index');
        Route::get('/create', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/inventaris");
        })->name('create');
        Route::post('/', [InventarisController::class, 'store'])->name('store');
        Route::get('/{kode}/edit', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/inventaris");
        })->name('edit');
        Route::put('/{kode}', [InventarisController::class, 'update'])->name('update');
        Route::delete('/{kode}', [InventarisController::class, 'destroy'])->name('destroy');
        Route::get('/export/pdf', [InventarisController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [InventarisController::class, 'exportExcel'])->name('export.excel');
    });

    // Kontrak
    Route::prefix('admin/kontrak')->name('kontrak.')->group(function () {
        Route::get('/', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/kontrak");
        })->name('index');
        Route::get('/create', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/kontrak");
        })->name('create');
        Route::post('/', [KontrakController::class, 'store'])->name('store');
        Route::get('/{kontrak}', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/kontrak");
        })->name('show');
        Route::get('/{kontrak}/edit', function () {
            $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
            return redirect()->away("{$frontendUrl}/kontrak");
        })->name('edit');
        Route::put('/{kontrak}', [KontrakController::class, 'update'])->name('update');
        Route::delete('/{kontrak}', [KontrakController::class, 'destroy'])->name('destroy');
        Route::get('/export/pdf', [KontrakController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [KontrakController::class, 'exportExcel'])->name('export.excel');
    });

    // Pengaturan
    Route::get('/admin/pengaturan', function () {
        $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        return redirect()->away($frontendUrl);
    })->name('pengaturan');
});
