<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupportTicketController;
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

    Route::resource('support-tickets', SupportTicketController::class)->except(['edit', 'update', 'destroy']);
    Route::post('support-tickets/{supportTicket}/reply', [SupportTicketController::class, 'reply'])->name('support-tickets.reply');
    Route::patch('support-tickets/{supportTicket}/status', [SupportTicketController::class, 'updateStatus'])->name('support-tickets.update-status');
    Route::get('/support-tickets/export/csv', [SupportTicketController::class, 'exportCsv'])->name('support-tickets.export.csv');
    Route::get('/support-tickets/export/pdf', [SupportTicketController::class, 'exportPdf'])->name('support-tickets.export.pdf');


});

require __DIR__.'/settings.php';
