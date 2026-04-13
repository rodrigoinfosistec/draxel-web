<?php

namespace App\Modules\PurchaseReceipt\Policies;

use App\Models\User;
use App\Modules\PurchaseReceipt\Models\PurchaseReceipt;

class PurchaseReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchaseReceipt.viewAnyPurchaseReceipt');
    }

    public function view(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchaseReceipt.viewPurchaseReceipt');
    }

    public function create(User $user): bool
    {
        return $user->can('purchaseReceipt.createPurchaseReceipt');
    }

    public function update(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchaseReceipt.updatePurchaseReceipt');
    }

    public function delete(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchaseReceipt.deletePurchaseReceipt');
    }

    public function post(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchaseReceipt.postPurchaseReceipt');
    }

    public function cancel(User $user, PurchaseReceipt $purchaseReceipt): bool
    {
        return $user->can('purchaseReceipt.cancelPurchaseReceipt');
    }

    public function export(User $user): bool
    {
        return $user->can('purchaseReceipt.exportPurchaseReceipt');
    }

    public function dashboard(User $user): bool
    {
        return $user->can('purchaseReceipt.viewDashboard');
    }
}
