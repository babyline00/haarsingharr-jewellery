<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->shareBranding();

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

    /**
     * Make branding available to the storefront and admin layouts without
     * every controller having to pass it through.
     */
    protected function shareBranding(): void
    {
        try {
            $brand = [
                'name'       => \App\Models\StoreSetting::get('store_name', config('app.name')),
                'logo'       => \App\Support\Brand::logoUrl(),
                'logoHeight' => \App\Support\Brand::logoHeight(),
                'favicon'    => \App\Support\Brand::faviconUrl(),
            ];
        } catch (\Throwable $e) {
            // The settings table may not exist yet during installation.
            $brand = [
                'name'       => config('app.name'),
                'logo'       => asset('images/logo.png'),
                'logoHeight' => 56,
                'favicon'    => asset('images/favicon.ico'),
            ];
        }

        View::share('brand', $brand);
    }

    /**
     * Named rate limiters referenced by the `throttle:<name>` route middleware.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 5)
            ->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('reset', fn (Request $request) => Limit::perMinutes(60, 3)
            ->by($request->input('email').'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinutes(30, 3)
            ->by($request->ip()));

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(5)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinutes(15, 5)
            ->by($request->ip()));

        RateLimiter::for('newsletter', fn (Request $request) => Limit::perMinutes(10, 3)
            ->by($request->ip()));

        RateLimiter::for('review', fn (Request $request) => Limit::perMinutes(60, 5)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('gift', fn (Request $request) => Limit::perMinute(12)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
