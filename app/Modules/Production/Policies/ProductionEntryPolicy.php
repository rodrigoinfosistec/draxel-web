<?php

namespace App\Modules\Production\Policies;

use App\Models\User;
use App\Modules\Production\Models\ProductionEntry;

class ProductionEntryPolicy
{
    public function viewDashboard(User $user): bool
    {
        return $this->allows($user, 'production.viewDashboard');
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'production.viewAnyEntry');
    }

    public function view(User $user, ProductionEntry $entry): bool
    {
        return $this->allows($user, 'production.viewEntry')
            && (int) $user->tenant_id === (int) $entry->tenant_id;
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'production.createEntry');
    }

    public function update(User $user, ProductionEntry $entry): bool
    {
        return $this->allows($user, 'production.updateEntry')
            && (int) $user->tenant_id === (int) $entry->tenant_id
            && $entry->isDraft();
    }

    public function delete(User $user, ProductionEntry $entry): bool
    {
        return $this->allows($user, 'production.deleteEntry')
            && (int) $user->tenant_id === (int) $entry->tenant_id
            && $entry->isDraft();
    }

    public function post(User $user, ProductionEntry $entry): bool
    {
        return $this->allows($user, 'production.postEntry')
            && (int) $user->tenant_id === (int) $entry->tenant_id
            && $entry->isDraft();
    }

    public function cancel(User $user, ProductionEntry $entry): bool
    {
        return $this->allows($user, 'production.cancelEntry')
            && (int) $user->tenant_id === (int) $entry->tenant_id
            && $entry->isPosted();
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'production.exportEntry');
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
