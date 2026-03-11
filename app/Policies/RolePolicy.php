<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array('roles.viewAny', $user->getAllPermissionSlugs(), true);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->tenant_id === $role->tenant_id
            && in_array('roles.view', $user->getAllPermissionSlugs(), true);
    }

    public function create(User $user): bool
    {
        return in_array('roles.create', $user->getAllPermissionSlugs(), true);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->tenant_id === $role->tenant_id
            && in_array('roles.update', $user->getAllPermissionSlugs(), true);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->tenant_id === $role->tenant_id
            && in_array('roles.delete', $user->getAllPermissionSlugs(), true);
    }
}
