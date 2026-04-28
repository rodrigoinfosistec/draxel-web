<?php

use App\Modules\Order\Http\Controllers\OrderController;
use App\Modules\Order\Http\Controllers\OrderDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('order')->name('order.')->group(function () {
        Route::get('/', [OrderDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/create', [OrderController::class, 'create'])->name('create');
            Route::post('/', [OrderController::class, 'store'])->name('store');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [OrderController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [OrderController::class, 'exportPdf'])->name('pdf');
            });

            Route::get('/{order}/export/pdf', [OrderController::class, 'exportOrderPdf'])->name('show.pdf');

            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
            Route::put('/{order}', [OrderController::class, 'update'])->name('update');
            Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');

            Route::post('/{order}/confirm', [OrderController::class, 'confirm'])->name('confirm');
            Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        });
    });
});
