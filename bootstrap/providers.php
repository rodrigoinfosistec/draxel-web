<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\HorizonServiceProvider::class,
    App\Modules\Worktime\Providers\WorktimeServiceProvider::class,
    App\Modules\Inventory\Providers\InventoryServiceProvider::class,
    App\Modules\Production\Providers\ProductionServiceProvider::class,
    App\Modules\Order\Providers\OrderServiceProvider::class,
    App\Modules\PurchaseReceipt\Providers\PurchaseReceiptServiceProvider::class,
];
