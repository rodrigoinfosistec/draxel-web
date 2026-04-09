<?php

use App\Modules\Production\Http\Controllers\ProductionDashboardController;
use App\Modules\Production\Http\Controllers\ProductionEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/', [ProductionDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('entries')->name('entries.')->group(function () {
            Route::get('/', [ProductionEntryController::class, 'index'])->name('index');
            Route::get('/export/csv', [ProductionEntryController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export/pdf', [ProductionEntryController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/create', [ProductionEntryController::class, 'create'])->name('create');
            Route::post('/', [ProductionEntryController::class, 'store'])->name('store');
            Route::get('/{entry}/pdf', [ProductionEntryController::class, 'showPdf'])->name('show.pdf');
            Route::get('/{entry}/edit', [ProductionEntryController::class, 'edit'])->name('edit');
            Route::put('/{entry}', [ProductionEntryController::class, 'update'])->name('update');
            Route::post('/{entry}/post', [ProductionEntryController::class, 'post'])->name('post');
            Route::post('/{entry}/cancel', [ProductionEntryController::class, 'cancel'])->name('cancel');
            Route::delete('/{entry}', [ProductionEntryController::class, 'destroy'])->name('destroy');
            Route::get('/{entry}', [ProductionEntryController::class, 'show'])->name('show');
        });
    });
});
