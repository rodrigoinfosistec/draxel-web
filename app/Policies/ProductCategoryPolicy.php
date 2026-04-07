<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\User;

class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('productCategories.viewAny');
    }

    public function view(User $user, ProductCategory $productCategory): bool
    {
        return $user->tenant_id === $productCategory->tenant_id
            && $user->hasPermission('productCategories.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('productCategories.create');
    }

    public function update(User $user, ProductCategory $productCategory): bool
    {
        return $user->tenant_id === $productCategory->tenant_id
            && $user->hasPermission('productCategories.update');
    }

    public function delete(User $user, ProductCategory $productCategory): bool
    {
        return $user->tenant_id === $productCategory->tenant_id
            && $user->hasPermission('productCategories.delete');
    }
}
