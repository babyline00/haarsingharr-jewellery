<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('staff.view') || $user->can('customers.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model)
            || $user->can('staff.view')
            || $user->can('customers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('staff.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('staff.update') || $user->can('customers.update');
    }

    public function delete(User $user, User $model): bool
    {
        return ! $user->is($model) && $user->can('staff.delete');
    }
}
