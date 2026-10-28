<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.view');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->can('reviews.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Review $review): bool
    {
        return $user->can('reviews.update') || $user->can('reviews.moderate');
    }

    public function approve(User $user, Review $review): bool
    {
        return $user->can('reviews.approve') || $user->can('reviews.moderate');
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->can('reviews.delete');
    }
}
