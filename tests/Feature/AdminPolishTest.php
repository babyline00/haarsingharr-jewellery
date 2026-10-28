<?php

use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\CartService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Admin polish tests (Phase 8)
|--------------------------------------------------------------------------
|
| Permission-matrix regression (every role × admin routes), the ⌘K palette
| suggest endpoint, the COD limit setting actually limiting COD, and the
| accessibility baseline of the admin shell.
|
*/

beforeEach(function () {
    $this->seedRolesAndPermissions();
    $this->actingAs(createAdmin());
});

// -------------------------------------------------------------------------
// ⌘K command palette (§8.30)
// -------------------------------------------------------------------------

test('the palette suggest endpoint returns permission-aware groups', function () {
    Product::factory()->create(['name' => 'Sweep Pendant', 'sku' => 'SWP-1']);

    $response = $this->getJson(route('admin.search.suggest', ['q' => 'Sweep']))
        ->assertOk()
        ->assertJsonStructure(['orders', 'customers', 'products', 'actions']);

    expect(collect($response->json('products'))->pluck('title'))->toContain('Sweep Pendant');
});

test('the suggest endpoint hides groups and actions the role cannot see', function () {
    $staff = \App\Models\User::factory()->create(['type' => 'admin', 'status' => 'active']);
    $staff->givePermissionTo(['orders.view', 'orders.update']);
    $this->actingAs($staff);

    $order = \App\Models\Order::factory()->create(['number' => 'HS-SUGGEST-1']);

    $response = $this->getJson(route('admin.search.suggest', ['q' => $order->number]))
        ->assertOk();

    expect(collect($response->json('orders'))->pluck('title'))->toContain($order->number)
        ->and($response->json('customers'))->toBeEmpty()
        ->and($response->json('products'))->toBeEmpty();

    $actions = collect($response->json('actions'))->pluck('title')->values();
    expect($actions)->toContain('Orders')
        ->and($actions)->not->toContain('Customers')
        ->and($actions)->not->toContain('Permission matrix');
});

// -------------------------------------------------------------------------
// COD limit (P0 regression) — the admin setting must actually limit COD
// -------------------------------------------------------------------------

test('the cod_max_order_value setting is enforced at checkout', function () {
    StoreSetting::set('cod_max_order_value', 500, 'checkout');

    $customer = \App\Models\User::factory()->create(['type' => 'customer', 'status' => 'active']);
    $customer->assignRole('customer');
    $this->actingAs($customer);

    $product = Product::factory()->create();
    $variant = \App\Models\ProductVariant::factory()->create([
        'product_id' => $product->id, 'regular_price' => 1200, 'stock_quantity' => 50,
    ]);

    $cart = \App\Models\Cart::factory()->create(['user_id' => $customer->id]);
    $cart->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price' => 1200,
        'line_total' => 1200,
    ]);
    $cart->recalculate();

    // The COD ceiling is enforced by CheckoutController before an order is
    // created, so the limit must surface as a validation error on the
    // payment_method field.
    $this->post(route('checkout.store'), [
        'email'          => $customer->email,
        'phone_country'  => '+92',
        'phone'          => '+92 300 0000000',
        'shipping_first_name' => 'Haarsingharr',
        'shipping_last_name'  => 'Test',
        'shipping_address_1'  => 'House 5, Street 2',
        'shipping_city'       => 'Lahore',
        'shipping_province'   => 'punjab',
        'shipping_country'    => 'PK',
        'shipping_postal_code' => '54000',
        'shipping_method'     => 'standard',
        'payment_method'      => 'cod',
        'agree_terms'         => '1',
        'idempotency_key'     => 'cod-limit-'.uniqid(),
    ])->assertSessionHasErrors(['payment_method']);

    // The guard runs before any order is written.
    expect(\App\Models\Order::where('guest_email', $customer->email)->exists())->toBeFalse();
});

// -------------------------------------------------------------------------
// Permission matrix (§11) — every role × admin routes
// -------------------------------------------------------------------------

test('every role stays inside its permission sandbox on admin routes', function () {
    $routes = collect(Route::getRoutes())
        ->filter(function ($route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods(), true)) {
                return false;
            }
            $suffix = Str::after($name, 'admin.');

            return str_ends_with($suffix, '.index') || in_array($suffix, ['database.info', 'reports.sales'], true);
        })
        ->values();

    $sample = $routes->first(fn ($r) => Str::after($r->getName(), 'admin.') === 'reports.sales');
    $routeUris = $routes->pluck('uri')->all();

    foreach (polishRoleActors() as $slug => $actor) {
        $this->actingAs($actor);

        foreach ($routeUris as $url) {
            $res = $this->get('/'.ltrim($url, '/'));
            $status = $res->status();

            // Never a 5xx, whatever the role.
            expect($status)->toBeLessThan(500, "role {$slug} got a 5xx on {$url}");

            // And never a sneaky 200 outside the role's sandbox.
            if ($slug !== 'super-admin') {
                $module = polishRouteModule($url);
                if ($module !== null && ! in_array($module, polishRoleModules($slug), true)) {
                    expect($status)->toBeIn([403, 302, 419, 429],
                        "role {$slug} reached {$url} outside its sandbox (status {$status})");
                }
            }
        }
    }

    expect($sample)->not->toBeNull();
});

test('a customer account never reaches admin screens', function () {
    $customer = \App\Models\User::factory()->create(['type' => 'customer']);
    $customer->assignRole('customer');
    $this->actingAs($customer);

    $this->get('/admin')->assertForbidden();
});

// -------------------------------------------------------------------------
// Accessibility basics (§9) + palette markup
// -------------------------------------------------------------------------

test('the admin shell ships the accessibility baseline', function () {
    $shell = $this->withSession(['success' => 'Saved.'])->get('/admin')->content();
    $orders = $this->get(route('admin.orders.index'))->content();

    expect($shell)->toContain('#main-content')          // skip link
        ->and($shell)->toContain('<main')               // landmark
        ->and($shell)->toContain('aria-haspopup="dialog"') // palette trigger
        ->and($shell)->toContain('role="dialog"')       // palette overlay
        ->and($shell)->toContain('aria-modal="true"')   // palette overlay modal semantics
        ->and($shell)->toContain('role="status"')       // flash toast
        ->and($orders)->toContain('aria-label="Select all orders on this page"');
});

test('settings fields wire help hints into aria-describedby', function () {
    $html = $this->get(route('admin.settings.index'))->content();

    expect($html)->toContain('aria-describedby="setting-cod_max_order_value-hint"')
        ->and($html)->toContain('id="setting-cod_max_order_value-hint"');
});

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

if (! function_exists('polishRoleActors')) {
    /**
     * A signed-in staff member per seeded role (customer role excluded —
     * it is covered separately by the 403 test).
     */
    function polishRoleActors(): array
    {
        $actors = [];
        foreach (['super-admin', 'store-admin', 'product-manager', 'inventory-manager',
                   'order-manager', 'customer-support', 'marketing-manager', 'report-viewer'] as $slug) {
            $user = \App\Models\User::factory()->create([
                'type' => 'admin', 'status' => 'active',
                'first_name' => 'Matrix', 'last_name' => ucfirst(str_replace('-', ' ', $slug)),
            ]);
            $user->assignRole($slug);
            $actors[$slug] = $user;
        }

        return $actors;
    }
}

if (! function_exists('polishRoleModules')) {
    /** Modules each role may reach, mirroring RolesAndPermissionsSeeder. */
    function polishRoleModules(string $slug): array
    {
        return match ($slug) {
            'store-admin' => ['products', 'categories', 'collections', 'inventory', 'orders', 'payments',
                'refunds', 'customers', 'coupons', 'reviews', 'homepage', 'cms', 'seo', 'reports',
                'settings', 'shipping', 'store_locations', 'newsletter', 'contacts', 'exchanges',
                'faqs', 'banners', 'questions', 'blog', 'menus', 'email_templates', 'notify'],
            'product-manager' => ['products', 'categories', 'collections', 'inventory', 'notify'],
            'inventory-manager' => ['inventory', 'notify'],
            'order-manager' => ['orders', 'payments', 'refunds', 'shipping', 'exchanges', 'notify'],
            'customer-support' => ['orders', 'customers', 'products', 'reviews', 'contacts', 'questions', 'notify'],
            'marketing-manager' => ['coupons', 'banners', 'homepage', 'cms', 'newsletter', 'reviews',
                'questions', 'blog', 'menus', 'email_templates', 'notify'],
            'report-viewer' => ['reports', 'notify'],
            default => [], // super-admin: sandbox check skipped
        };
    }
}

if (! function_exists('polishRouteModule')) {
    /** Permission module for an admin route URI (null = no module locked down). */
    function polishRouteModule(string $url): ?string
    {
        $path = trim(parse_url($url, PHP_URL_PATH) ?? $url, '/');

        return match ($path) {
            'admin' => null,                                     // dashboard: auth-only
            'admin/notifications' => null,                        // auth-only notification centre
            'admin/users' => 'customers',                         // combined directory: staff.view|customers.view
            'admin/settings', 'admin/system', 'admin/database/info' => 'settings',
            'admin/audit-logs', 'admin/security-events', 'admin/active-sessions' => 'audit',
            'admin/emails' => 'email_templates',
            'admin/pages', 'admin/seo' => 'cms',
            'admin/homepage-sections' => 'homepage',
            'admin/store-locations' => 'store_locations',
            'admin/enquiries' => 'contacts',
            default => preg_match('#^admin/([a-z0-9-]+)/#', $path.'/', $m)
                ? ($m[1] === 'reports' ? 'reports' : $m[1])
                : null,
        };
    }
}
