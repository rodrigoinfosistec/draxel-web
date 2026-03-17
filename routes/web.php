<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeTimeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ParametersController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');

    Route::post('/company/switch', function (Request $request) {
        $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        session(['current_company_id' => $request->company_id]);

        return back();
    })->name('company.switch');

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [UserController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [UserController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [RoleController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [RoleController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [AuditController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [AuditController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'index'])->name('index');
        Route::get('/create', [SupportTicketController::class, 'create'])->name('create');
        Route::post('/', [SupportTicketController::class, 'store'])->name('store');
        Route::get('/{supportTicket}', [SupportTicketController::class, 'show'])->name('show');

        Route::post('/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('reply');
        Route::patch('/{supportTicket}/status', [SupportTicketController::class, 'updateStatus'])->name('update-status');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [SupportTicketController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [SupportTicketController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('parameters')->name('parameters.')->group(function () {
        Route::get('/', [ParametersController::class, 'index'])->name('index');
        Route::patch('/company-default-times', [ParametersController::class, 'updateCompanyDefaultTimes'])->name('company-default-times.update');
    });

    Route::prefix('positions')->name('positions.')->group(function () {
        Route::get('/', [PositionController::class, 'index'])->name('index');
        Route::get('/create', [PositionController::class, 'create'])->name('create');
        Route::post('/', [PositionController::class, 'store'])->name('store');
        Route::get('/{position}/edit', [PositionController::class, 'edit'])->name('edit');
        Route::put('/{position}', [PositionController::class, 'update'])->name('update');
        Route::delete('/{position}', [PositionController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [PositionController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [PositionController::class, 'exportPdf'])->name('pdf');
        });
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [EmployeeController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [EmployeeController::class, 'exportPdf'])->name('pdf');
        });

        Route::get('/{employee}/times', [EmployeeTimeController::class, 'edit'])->name('times.edit');
        Route::patch('/{employee}/times', [EmployeeTimeController::class, 'update'])->name('times.update');
    });

    Route::prefix('holidays')->name('holidays.')->group(function () {
        Route::get('/', [HolidayController::class, 'index'])->name('index');
        Route::get('/create', [HolidayController::class, 'create'])->name('create');
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::get('/{holiday}/edit', [HolidayController::class, 'edit'])->name('edit');
        Route::put('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');

        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/csv', [HolidayController::class, 'exportCsv'])->name('csv');
            Route::get('/pdf', [HolidayController::class, 'exportPdf'])->name('pdf');
        });
    });
});

require base_path('app/Modules/Worktime/Routes/web.php');

require __DIR__ . '/settings.php';
