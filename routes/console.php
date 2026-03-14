<?php

use App\Jobs\RunDatabaseBackup;
use App\Models\DatabaseBackup;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $backup = DatabaseBackup::query()->create([
        'type' => 'scheduled',
        'status' => 'pending',
        'disk' => 's3',
        'requested_by' => null,
    ]);

    RunDatabaseBackup::dispatch($backup->id);
})->dailyAt('03:00');
