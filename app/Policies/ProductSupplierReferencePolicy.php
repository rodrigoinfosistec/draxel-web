<?php

namespace App\Policies;

use App\Models\ProductSupplierReference;
use App\Models\User;

class ProductSupplierReferencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('supplierProductReferences.viewAny');
    }

    public function view(User $user, ProductSupplierReference $supplierProductReference): bool
    {
        return $user->tenant_id === $supplierProductReference->tenant_id
            && $user->hasPermission('supplierProductReferences.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('supplierProductReferences.create');
    }

    public function update(User $user, ProductSupplierReference $supplierProductReference): bool
    {
        return $user->tenant_id === $supplierProductReference->tenant_id
            && $user->hasPermission('supplierProductReferences.update');
    }

    public function delete(User $user, ProductSupplierReference $supplierProductReference): bool
    {
        return $user->tenant_id === $supplierProductReference->tenant_id
            && $user->hasPermission('supplierProductReferences.delete');
    }
}
