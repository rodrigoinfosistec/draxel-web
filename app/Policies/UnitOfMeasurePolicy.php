<?php

namespace App\Policies;

use App\Models\UnitOfMeasure;
use App\Models\User;

class UnitOfMeasurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('unitOfMeasures.viewAny');
    }

    public function view(User $user, UnitOfMeasure $unitOfMeasure): bool
    {
        return $user->tenant_id === $unitOfMeasure->tenant_id
            && $user->hasPermission('unitOfMeasures.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('unitOfMeasures.create');
    }

    public function update(User $user, UnitOfMeasure $unitOfMeasure): bool
    {
        return $user->tenant_id === $unitOfMeasure->tenant_id
            && $user->hasPermission('unitOfMeasures.update');
    }

    public function delete(User $user, UnitOfMeasure $unitOfMeasure): bool
    {
        return $user->tenant_id === $unitOfMeasure->tenant_id
            && $user->hasPermission('unitOfMeasures.delete');
    }
}
