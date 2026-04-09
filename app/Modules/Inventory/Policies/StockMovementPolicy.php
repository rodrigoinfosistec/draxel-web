<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\StockMovement;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'inventory.viewAnyStockMovement');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $this->allows($user, 'inventory.viewStockMovement')
            && (int) $user->tenant_id === (int) $stockMovement->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'inventory.createStockMovement');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'inventory.exportStockMovement');
    }

    protected function allows(User $user, string $permission): bool
    {
        if ((bool) $user->is_admin) {
            return true;
        }

        if (method_exists($user, 'hasPermission')) {
            return (bool) $user->hasPermission($permission);
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return (bool) $user->hasPermissionTo($permission);
        }

        return false;
    }
}
