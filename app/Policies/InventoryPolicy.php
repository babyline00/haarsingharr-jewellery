<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }

    public function update(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory.update');
    }

    public function delete(User $user, InventoryMovement $movement): bool
    {
        return $user->can('inventory.delete');
    }
}
