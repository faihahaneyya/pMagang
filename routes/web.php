<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarisController;
use App\Http\Controllers\KontrakController;
use App\Http\Controllers\BarcodeController;

/*
|--------------------------------------------------------------------------
| Public Route — Accessible without login (for QR code scanning)
|--------------------------------------------------------------------------
*/
Route::get('/inventaris/{kode}', [InventarisController::class, 'show'])
    ->name('inventaris.show')
    ->where('kode', '[A-Za-z0-9\-]+');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/export/pdf', [DashboardController::class, 'exportPdf'])->name('dashboard.export.pdf');
Route::get('/dashboard/export/excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export.excel');

// Inventaris
Route::prefix('admin/inventaris')->name('inventaris.')->group(function () {
    Route::get('/', [InventarisController::class, 'index'])->name('index');
    Route::get('/create', [InventarisController::class, 'create'])->name('create');
    Route::post('/', [InventarisController::class, 'store'])->name('store');
    Route::get('/{kode}/edit', [InventarisController::class, 'edit'])->name('edit');
    Route::put('/{kode}', [InventarisController::class, 'update'])->name('update');
    Route::delete('/{kode}', [InventarisController::class, 'destroy'])->name('destroy');
    Route::get('/export/pdf', [InventarisController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/export/excel', [InventarisController::class, 'exportExcel'])->name('export.excel');
});

// Kontrak
Route::prefix('admin/kontrak')->name('kontrak.')->group(function () {
    Route::get('/', [KontrakController::class, 'index'])->name('index');
    Route::get('/create', [KontrakController::class, 'create'])->name('create');
    Route::post('/', [KontrakController::class, 'store'])->name('store');
    Route::get('/{kontrak}', [KontrakController::class, 'show'])->name('show');
    Route::get('/{kontrak}/edit', [KontrakController::class, 'edit'])->name('edit');
    Route::put('/{kontrak}', [KontrakController::class, 'update'])->name('update');
    Route::delete('/{kontrak}', [KontrakController::class, 'destroy'])->name('destroy');
    Route::get('/export/pdf', [KontrakController::class, 'exportPdf'])->name('export.pdf');
    Route::get('/export/excel', [KontrakController::class, 'exportExcel'])->name('export.excel');
});

// Barcode
Route::prefix('admin/barcode')->name('barcode.')->group(function () {
    Route::get('/', [BarcodeController::class, 'index'])->name('index');
    Route::get('/{kode}/download', [BarcodeController::class, 'download'])->name('download');
    Route::get('/{kode}/print', [BarcodeController::class, 'print'])->name('print');
    Route::post('/print-all', [BarcodeController::class, 'printAll'])->name('print-all');
});

// Pengaturan
Route::get('/admin/pengaturan', function () {
    return view('pengaturan');
})->name('pengaturan');
