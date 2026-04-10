<?php

namespace App\Modules\Order\Policies;

use App\Models\User;
use App\Modules\Order\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('order.viewAnyOrder');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasPermission('order.viewOrder');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('order.createOrder');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('order.updateOrder');
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermission('order.deleteOrder');
    }

    public function confirm(User $user, Order $order): bool
    {
        return $user->hasPermission('order.confirmOrder');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasPermission('order.cancelOrder');
    }

    public function export(User $user): bool
    {
        return $user->hasPermission('order.exportOrder');
    }
}
