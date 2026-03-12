<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('users.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $target->tenant_id
            && $user->hasPermission('users.view');
    }

    public function create(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $target->tenant_id
            && $user->hasPermission('users.update');
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $target->tenant_id
            && $user->hasPermission('users.delete');
    }
}
