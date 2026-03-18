<?php

namespace App\Modules\Worktime\Providers;

use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\EmployeeEvent;
use App\Modules\Worktime\Policies\ClockRecordPolicy;
use App\Modules\Worktime\Policies\EmployeeEventPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WorktimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/worktime.php', 'worktime');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Gate::policy(EmployeeEvent::class, EmployeeEventPolicy::class);
        Gate::policy(ClockRecord::class, ClockRecordPolicy::class);
    }
}
