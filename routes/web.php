<?php

use App\Jobs\TestIntegrationJob;
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

Route::get('/test/integration', function () {
    TestIntegrationJob::dispatch();

    return 'Job enviado para fila.';
})->middleware(['auth', 'requireTenant']);

require __DIR__.'/settings.php';
