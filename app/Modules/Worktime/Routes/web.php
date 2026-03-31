<?php

use App\Modules\Worktime\Http\Controllers\BankHourController;
use App\Modules\Worktime\Http\Controllers\ClockRecordController;
use App\Modules\Worktime\Http\Controllers\ClockRecordImportController;
use App\Modules\Worktime\Http\Controllers\EmployeeEventController;
use App\Modules\Worktime\Http\Controllers\HourBankSnapshotController;
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
            Route::delete('/{clockRecordImport}', [ClockRecordImportController::class, 'destroy'])->name('destroy');
            Route::post('/{clockRecordImport}/launch', [ClockRecordImportController::class, 'launch'])->name('launch');
            Route::post('/{clockRecordImport}/revert', [ClockRecordImportController::class, 'revert'])->name('revert');
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

        Route::prefix('bank-hours')->name('bank-hours.')->group(function () {
            Route::get('/', [BankHourController::class, 'index'])->name('index');
            Route::get('/create', [BankHourController::class, 'create'])->name('create');
            Route::post('/', [BankHourController::class, 'store'])->name('store');
            Route::get('/{bankHourEntry}/edit', [BankHourController::class, 'edit'])->name('edit');
            Route::put('/{bankHourEntry}', [BankHourController::class, 'update'])->name('update');

            Route::get('/{bankHourAccount}/export/pdf', [BankHourController::class, 'exportEmployeePdf'])
                ->name('export.employee-pdf');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [BankHourController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [BankHourController::class, 'exportPdf'])->name('pdf');
            });
        });

        Route::prefix('hour-bank-snapshots')->name('hour-bank-snapshots.')->group(function () {
            Route::get('/', [HourBankSnapshotController::class, 'index'])->name('index');
            Route::get('/create', [HourBankSnapshotController::class, 'create'])->name('create');
            Route::post('/', [HourBankSnapshotController::class, 'store'])->name('store');
            Route::get('/{hourBankSnapshot}', [HourBankSnapshotController::class, 'show'])->name('show');
            Route::delete('/{hourBankSnapshot}', [HourBankSnapshotController::class, 'destroy'])->name('destroy');

            Route::post('/{hourBankSnapshot}/employees', [HourBankSnapshotController::class, 'addEmployees'])->name('employees.store');
            Route::delete('/{hourBankSnapshot}/employees/{hourBankSnapshotEmployee}', [HourBankSnapshotController::class, 'removeEmployee'])->name('employees.destroy');

            Route::post('/{hourBankSnapshot}/consolidate', [HourBankSnapshotController::class, 'consolidate'])->name('consolidate');
            Route::post('/{hourBankSnapshot}/reverse', [HourBankSnapshotController::class, 'reverse'])->name('reverse');

            Route::prefix('export')->name('export.')->group(function () {
                Route::get('/csv', [HourBankSnapshotController::class, 'exportCsv'])->name('csv');
                Route::get('/pdf', [HourBankSnapshotController::class, 'exportPdf'])->name('pdf');
            });

            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/{hourBankSnapshot}/general-preview', [HourBankSnapshotController::class, 'exportGeneralPreview'])->name('general-preview');
                Route::get('/{hourBankSnapshot}/general-consolidated', [HourBankSnapshotController::class, 'exportGeneralConsolidated'])->name('general-consolidated');
                Route::get('/{hourBankSnapshot}/employees/{hourBankSnapshotEmployee}', [HourBankSnapshotController::class, 'exportEmployeeReport'])->name('employee');
            });
        });
    });
});
