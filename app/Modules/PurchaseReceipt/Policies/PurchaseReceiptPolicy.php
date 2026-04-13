<?php

namespace App\Modules\PurchaseReceipt\Policies;

use App\Models\User;
use App\Modules\PurchaseReceipt\Models\PurchaseReceipt;

class PurchaseReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('purchaseReceipt.viewAnyPurchaseReceipt');
    }

    public function view(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->hasPermission('purchaseReceipt.viewPurchaseReceipt');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('purchaseReceipt.createPurchaseReceipt');
    }

    public function update(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->hasPermission('purchaseReceipt.updatePurchaseReceipt');
    }

    public function delete(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->hasPermission('purchaseReceipt.deletePurchaseReceipt');
    }

    public function post(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->hasPermission('purchaseReceipt.postPurchaseReceipt');
    }

    public function cancel(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->hasPermission('purchaseReceipt.cancelPurchaseReceipt');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('purchaseReceipt.exportPurchaseReceipt');
    }

    public function dashboard(User $user): bool
    {
        return $user->hasPermission('purchaseReceipt.viewDashboard');
    }
}
