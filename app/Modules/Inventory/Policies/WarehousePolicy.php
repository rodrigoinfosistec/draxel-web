<?php

namespace App\Modules\Inventory\Policies;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;

class WarehousePolicy
{
    public function viewDashboard(User $user): bool
    {
        return $this->allows($user, 'inventory.viewDashboard');
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'inventory.viewAnyWarehouse');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->allows($user, 'inventory.viewWarehouse')
            && (int) $user->tenant_id === (int) $warehouse->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'inventory.createWarehouse');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $this->allows($user, 'inventory.updateWarehouse')
            && (int) $user->tenant_id === (int) $warehouse->tenant_id;
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $this->allows($user, 'inventory.deleteWarehouse')
            && (int) $user->tenant_id === (int) $warehouse->tenant_id;
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'inventory.exportWarehouse');
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
