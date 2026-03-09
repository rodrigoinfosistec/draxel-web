<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'users.viewAny');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $target->tenant_id
            && $this->hasPermission($user, 'users.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'users.create');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->tenant_id === $target->tenant_id
            && $this->hasPermission($user, 'users.update');
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
            && $this->hasPermission($user, 'users.delete');
    }

    protected function hasPermission(User $user, string $slug): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->roles()
            ->where('roles.tenant_id', $user->tenant_id)
            ->where('roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($slug) {
                $query
                    ->where('permissions.slug', $slug)
                    ->where('permissions.is_active', true);
            })
            ->exists();
    }
}
