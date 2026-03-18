<?php

namespace App\Modules\Worktime\Database\Seeders;

use App\Modules\Worktime\Models\ClockDevice;
use Illuminate\Database\Seeder;

class WorktimeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('worktime.clock_devices', []) as $clockDevice) {
            ClockDevice::query()->updateOrCreate(
                [
                    'slug' => $clockDevice['slug'],
                ],
                [
                    'name' => $clockDevice['name'],
                ],
            );
        }
    }
}
