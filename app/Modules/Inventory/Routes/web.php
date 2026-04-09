<?php

use App\Modules\Inventory\Http\Controllers\InventoryDashboardController;
use App\Modules\Inventory\Http\Controllers\InventoryPositionController;
use App\Modules\Inventory\Http\Controllers\StockMovementController;
use App\Modules\Inventory\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('warehouses')->name('warehouses.')->group(function () {
            Route::get('/', [WarehouseController::class, 'index'])->name('index');
            Route::get('/export/csv', [WarehouseController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export/pdf', [WarehouseController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/create', [WarehouseController::class, 'create'])->name('create');
            Route::post('/', [WarehouseController::class, 'store'])->name('store');
            Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('edit');
            Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('movements')->name('movements.')->group(function () {
            Route::get('/', [StockMovementController::class, 'index'])->name('index');
            Route::get('/export/csv', [StockMovementController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export/pdf', [StockMovementController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/create', [StockMovementController::class, 'create'])->name('create');
            Route::post('/', [StockMovementController::class, 'store'])->name('store');
        });

        Route::prefix('positions')->name('positions.')->group(function () {
            Route::get('/', [InventoryPositionController::class, 'byWarehouse'])->name('index');
            Route::get('/export/csv', [InventoryPositionController::class, 'exportByWarehouseCsv'])->name('export.csv');
            Route::get('/export/pdf', [InventoryPositionController::class, 'exportByWarehousePdf'])->name('export.pdf');

            Route::get('/consolidated', [InventoryPositionController::class, 'consolidated'])->name('consolidated');
            Route::get('/consolidated/export/csv', [InventoryPositionController::class, 'exportConsolidatedCsv'])->name('consolidated.export.csv');
            Route::get('/consolidated/export/pdf', [InventoryPositionController::class, 'exportConsolidatedPdf'])->name('consolidated.export.pdf');
        });
    });
});
