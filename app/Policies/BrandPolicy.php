<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('brands.viewAny');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->tenant_id === $brand->tenant_id
            && $user->hasPermission('brands.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('brands.create');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->tenant_id === $brand->tenant_id
            && $user->hasPermission('brands.update');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->tenant_id === $brand->tenant_id
            && $user->hasPermission('brands.delete');
    }
}
