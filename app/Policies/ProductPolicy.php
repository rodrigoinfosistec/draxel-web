<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function dashboard(User $user): bool
    {
        return $user->hasPermission('products.dashboard');
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('products.viewAny');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->hasPermission('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->tenant_id === $product->tenant_id
            && $user->hasPermission('products.delete');
    }
}
