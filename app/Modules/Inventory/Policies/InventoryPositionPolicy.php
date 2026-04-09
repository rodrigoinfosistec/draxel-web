<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\ProductStock;

class InventoryPositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'inventory.viewAnyProductStock');
    }

    public function view(User $user, ProductStock $productStock): bool
    {
        return $this->allows($user, 'inventory.viewProductStock')
            && (int) $user->tenant_id === (int) $productStock->tenant_id;
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'inventory.exportProductStock');
    }

    public function viewConsolidated(User $user): bool
    {
        return $this->allows($user, 'inventory.viewInventoryPosition');
    }

    public function exportConsolidated(User $user): bool
    {
        return $this->allows($user, 'inventory.exportInventoryPosition');
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
