<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id
            || $order->guest_email === $user->email
            || $user->can('orders.view');
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($order->user_id !== $user->id && $order->guest_email !== $user->email) {
            return $user->can('orders.cancel');
        }
        return $order->isCancellable();
    }

    public function viewAny(User $user): bool
    {
        return $user->can('orders.view');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->can('orders.update');
    }
}
