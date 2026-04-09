<?php

namespace App\Modules\Production\Providers;

use App\Modules\Production\Models\ProductionEntry;
use App\Modules\Production\Policies\ProductionEntryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ProductionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/production.php', 'production');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Gate::policy(ProductionEntry::class, ProductionEntryPolicy::class);
    }
}
