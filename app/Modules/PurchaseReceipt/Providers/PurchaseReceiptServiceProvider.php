<?php

namespace App\Modules\PurchaseReceipt\Providers;

use App\Modules\PurchaseReceipt\Models\PurchaseReceipt;
use App\Modules\PurchaseReceipt\Policies\PurchaseReceiptPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PurchaseReceiptServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/purchase-receipt.php', 'PurchaseReceipt');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Gate::policy(PurchaseReceipt::class, PurchaseReceiptPolicy::class);
    }
}
