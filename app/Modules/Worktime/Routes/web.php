<?php

use App\Modules\Worktime\Http\Controllers\EmployeeEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('worktime')->name('worktime.')->group(function () {
        Route::prefix('employee-events')->name('employee-events.')->group(function () {
            Route::get('/', [EmployeeEventController::class, 'index'])->name('index');
            Route::get('/create', [EmployeeEventController::class, 'create'])->name('create');
            Route::post('/', [EmployeeEventController::class, 'store'])->name('store');
            Route::get('/{employeeEvent}/edit', [EmployeeEventController::class, 'edit'])->name('edit');
            Route::put('/{employeeEvent}', [EmployeeEventController::class, 'update'])->name('update');
            Route::delete('/{employeeEvent}', [EmployeeEventController::class, 'destroy'])->name('destroy');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [EmployeeEventController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [EmployeeEventController::class, 'exportPdf'])->name('pdf');
            });
        });
    });
});
