<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Order::class         => \App\Policies\OrderPolicy::class,
        \App\Models\Address::class        => \App\Policies\AddressPolicy::class,
        \App\Models\Product::class        => \App\Policies\ProductPolicy::class,
        \App\Models\Category::class       => \App\Policies\CategoryPolicy::class,
        \App\Models\InventoryMovement::class => \App\Policies\InventoryPolicy::class,
        \App\Models\User::class           => \App\Policies\UserPolicy::class,
        \App\Models\Coupon::class         => \App\Policies\CouponPolicy::class,
        \App\Models\Review::class         => \App\Policies\ReviewPolicy::class,
        \App\Models\Refund::class         => \App\Policies\RefundPolicy::class,
        \App\Models\CmsPage::class        => \App\Policies\CmsPagePolicy::class,
    ];

    public function boot(): void
    {
        // Super Admin bypass — defined as a Gate so any subsequent can() check
        // returns true for the super-admin role without us repeating it everywhere.
        Gate::before(function ($user, $ability) {
            if ($user instanceof \App\Models\User && $user->isSuperAdmin()) {
                return true;
            }
            return null; // fall through to other gates
        });
    }
}
