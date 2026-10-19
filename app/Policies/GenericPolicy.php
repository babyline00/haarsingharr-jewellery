<?php

namespace App\Policies;

use App\Models\User;

/**
 * Generic permission gate for any other model — implemented per-model in their
 * respective policy classes. This is a placeholder used by Gate::before
 * when no model-specific policy exists yet.
 */
class GenericPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }
}
