<?php

use App\Modules\Worktime\Http\Controllers\ClockRecordController;
use App\Modules\Worktime\Http\Controllers\ClockRecordImportController;
use App\Modules\Worktime\Http\Controllers\EmployeeEventController;
use App\Modules\Worktime\Http\Controllers\WorktimeApurationController;
use App\Modules\Worktime\Http\Controllers\WorktimeDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::prefix('worktime')->name('worktime.')->group(function () {
        Route::get('/', [WorktimeDashboardController::class, 'index'])->name('dashboard');

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

        Route::prefix('clock-records')->name('clock-records.')->group(function () {
            Route::get('/', [ClockRecordController::class, 'index'])->name('index');
            Route::get('/create', [ClockRecordController::class, 'create'])->name('create');
            Route::post('/', [ClockRecordController::class, 'store'])->name('store');
            Route::get('/{clockRecord}/edit', [ClockRecordController::class, 'edit'])->name('edit');
            Route::put('/{clockRecord}', [ClockRecordController::class, 'update'])->name('update');
            Route::delete('/{clockRecord}', [ClockRecordController::class, 'destroy'])->name('destroy');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [ClockRecordController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [ClockRecordController::class, 'exportPdf'])->name('pdf');
            });
        });

        Route::prefix('clock-record-imports')->name('clock-record-imports.')->group(function () {
            Route::get('/', [ClockRecordImportController::class, 'index'])->name('index');
            Route::get('/create', [ClockRecordImportController::class, 'create'])->name('create');
            Route::post('/', [ClockRecordImportController::class, 'store'])->name('store');
            Route::get('/{clockRecordImport}', [ClockRecordImportController::class, 'show'])->name('show');
            Route::post('/{clockRecordImport}/launch', [ClockRecordImportController::class, 'launch'])->name('launch');
            Route::post('/{clockRecordImport}/items/{clockRecordImportItem}/ignore', [ClockRecordImportController::class, 'ignoreItem'])->name('items.ignore');
            Route::post('/{clockRecordImport}/items/{clockRecordImportItem}/resolve-employee', [ClockRecordImportController::class, 'resolveEmployee'])->name('items.resolve-employee');
            Route::post('/{clockRecordImport}/adjust-times', [ClockRecordImportController::class, 'adjustTimes'])->name('adjust-times');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [ClockRecordImportController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [ClockRecordImportController::class, 'exportPdf'])->name('pdf');
            });
        });

        Route::prefix('apurations')->name('apurations.')->group(function () {
            Route::get('/', [WorktimeApurationController::class, 'index'])->name('index');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [WorktimeApurationController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [WorktimeApurationController::class, 'exportPdf'])->name('pdf');
            });
        });
    });
});
