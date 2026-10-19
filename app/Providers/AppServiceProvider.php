<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the strong_password rule.
        Validator::extend('strong_password', function ($attribute, $value) {
            return (new \App\Rules\StrongPassword())->passes($attribute, $value);
        });

        // Custom validation: prevent circular category relationships.
        Validator::extend('no_circular_parent', function ($attribute, $value, $parameters, $validator) {
            if (! $value) {
                return true;
            }
            $categoryId = $parameters[0] ?? null;
            $parentId = (int) $value;

            if ($parentId === (int) $categoryId) {
                return false;
            }

            // Walk up the parent chain to detect cycles.
            $visited = [(int) $categoryId];
            $current = \App\Models\Category::find($parentId);
            while ($current) {
                if (in_array($current->id, $visited, true)) {
                    return false;
                }
                $visited[] = $current->id;
                $current = $current->parent;
            }
            return true;
        });

        // International phone number validation (Pakistan + Gulf + select others).
        Validator::extend('international_phone', function ($attribute, $value) {
            return (bool) preg_match('/^\+?\d{6,15}$/', $value);
        });
    }
}
