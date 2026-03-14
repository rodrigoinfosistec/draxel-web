<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\ParametersController;
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
});

require __DIR__ . '/settings.php';
