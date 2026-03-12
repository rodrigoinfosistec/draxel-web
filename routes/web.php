<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified', 'requireTenant'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/company/switch', function (\Illuminate\Http\Request $request) {

    $request->validate([
        'company_id' => ['required', 'integer'],
    ]);

    session(['current_company_id' => $request->company_id]);

    return back();
})->middleware(['auth', 'requireTenant'])->name('company.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('roles', RoleController::class)->except(['show']);

    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export/csv', [AuditController::class, 'exportCsv'])->name('audit.export.csv');
    Route::get('/audit/export/pdf', [AuditController::class, 'exportPdf'])->name('audit.export.pdf');
});

require __DIR__.'/settings.php';
