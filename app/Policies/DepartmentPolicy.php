<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('departments.viewAny');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->tenant_id === $department->tenant_id
            && $user->hasPermission('departments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('departments.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->tenant_id === $department->tenant_id
            && $user->hasPermission('departments.update');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->tenant_id === $department->tenant_id
            && $user->hasPermission('departments.delete');
    }
}
