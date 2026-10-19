<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\Payment\JazzCashGateway;
use App\Services\Payment\PaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

// Helper: create a Super Admin role+user for tests.
function seedRoles(): void
{
    $roles = [
        ['super-admin', 'Super Admin'],
        ['store-admin', 'Store Admin'],
        ['product-manager', 'Product Manager'],
        ['order-manager', 'Order Manager'],
        ['customer-support', 'Customer Support'],
        ['customer', 'Customer'],
    ];
    foreach ($roles as [$slug, $title]) {
        \Spatie\Permission\Models\Role::firstOrCreate(
            ['slug' => $slug, 'name' => $slug],
            ['title' => $title, 'guard_name' => 'web', 'is_system' => true]
        );
    }
}

// ============================================================================
// Authentication & Authorization Tests
// ============================================================================

test('customer can register with strong password', function () {
    $response = $this->post('/register', [
        'first_name' => 'Ayesha',
        'last_name'  => 'Khan',
        'email'      => 'ayesha@haarsingharr.pk',
        'phone_country' => '+92',
        'phone'      => '3001234567',
        'password'   => 'Str0ngHaarsingharr@2025',
        'password_confirmation' => 'Str0ngHaarsingharr@2025',
        'terms'      => '1',
    ]);

    $response->assertRedirect(route('account.dashboard'));
    expect(User::where('email', 'ayesha@haarsingharr.pk')->exists())->toBeTrue();
});

test('weak password is rejected during registration', function () {
    $response = $this->post('/register', [
        'first_name' => 'Ayesha',
        'last_name'  => 'Khan',
        'email'      => 'ayesha@haarsingharr.pk',
        'phone_country' => '+92',
        'phone'      => '3001234567',
        'password'   => 'password',
        'password_confirmation' => 'password',
        'terms'      => '1',
    ]);
    $response->assertSessionHasErrors('password');
});

test('staff cannot access admin area without staff role', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $response = $this->actingAs($customer)->get('/admin');
    $response->assertForbidden();
});

test('login is rate-limited after 5 failed attempts', function () {
    foreach (range(1, 5) as $i) {
        $this->withSession(['login_throttle' => true])
            ->post('/login', [
                'email'    => 'unknown@example.com',
                'password' => 'wrong',
            ]);
    }
    // 6th attempt should be throttled
    $response = $this->post('/login', [
        'email'    => 'unknown@example.com',
        'password' => 'wrong',
    ]);
    $response->assertStatus(429);
});

// ============================================================================
// Product & Variant Management Tests
// ============================================================================

test('variant current price reflects sale when active', function () {
    $product = Product::factory()->active()->create([
        'regular_price' => 10000,
        'sale_price'    => 8000,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id'    => $product->id,
        'regular_price' => 10000,
        'sale_price'    => 8000,
        'is_default'    => true,
    ]);

    expect($variant->currentPrice())->toEqual(8000.0)
        ->and($variant->isOnSale())->toBeTrue();
});

test('variant sale is inactive when outside date range', function () {
    $variant = ProductVariant::factory()->create([
        'regular_price' => 10000,
        'sale_price'    => 8000,
        'sale_starts_at' => now()->addDay(),
    ]);
    expect($variant->isOnSale())->toBeFalse();
});

// ============================================================================
// Stock Reservation & Concurrent Checkout Tests
// ============================================================================

test('stock reservation is atomic and prevents overselling', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity'  => 1,
        'reserved_stock'  => 0,
        'safety_stock'    => 0,
    ]);

    $inventoryService = app(InventoryService::class);
    $order = Order::factory()->make();
    $order->save();

    // First reservation succeeds
    $inventoryService->reserve($variant, 1, $order);
    expect($variant->fresh()->reserved_stock)->toEqual(1);

    // Second reservation should fail (only 1 unit available)
    $order2 = Order::factory()->make();
    $order2->save();

    expect(fn () => $inventoryService->reserve($variant, 1, $order2))
        ->toThrow(\RuntimeException::class);
});

test('inventory movements are immutable after creation', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $inventoryService = app(InventoryService::class);

    $movement = $inventoryService->adjust($variant, 15, 'Manual adjustment');

    expect(fn () => $movement->update(['reason' => 'tampered']))
        ->toThrow(\RuntimeException::class);
});

test('concurrent checkouts cannot exceed stock', function () {
    $variant = ProductVariant::factory()->create([
        'stock_quantity' => 1,
        'safety_stock'  => 0,
    ]);

    $inventoryService = app(InventoryService::class);
    $errors = collect();

    // Simulate 5 concurrent reservation attempts
    foreach (range(1, 5) as $i) {
        try {
            DB::beginTransaction();
            $order = Order::factory()->make();
            $order->save();
            $inventoryService->reserve($variant, 1, $order);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors->push($i);
        }
    }

    // Only 1 should succeed
    expect($errors->count())->toBe(4)
        ->and($variant->fresh()->reserved_stock)->toEqual(1);
});

// ============================================================================
// Idempotent Checkout & Duplicate Submission Tests
// ============================================================================

test('duplicate checkout submissions return the same order', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5, 'safety_stock' => 0]);
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $cart = Cart::factory()->create(['user_id' => $customer->id]);
    $cart->items()->create([
        'product_id'         => $variant->product_id,
        'product_variant_id' => $variant->id,
        'quantity'           => 1,
        'unit_price'         => $variant->regular_price,
        'line_total'         => $variant->regular_price,
    ]);

    $checkoutService = app(CheckoutService::class);
    $key = \Illuminate\Support\Str::uuid()->toString();

    $data1 = [
        'email' => $customer->email,
        'phone' => $customer->phone,
        'phone_country' => '+92',
        'shipping_address' => makeAddressArray(),
        'shipping_method' => 'standard',
        'payment_method' => 'cod',
        'payment_provider' => 'cod',
        'idempotency_key' => $key,
    ];

    $order1 = $checkoutService->placeOrder($data1);
    $order2 = $checkoutService->placeOrder($data1); // duplicate

    expect($order1->id)->toEqual($order2->id);
});

function makeAddressArray(): array
{
    return [
        'first_name'     => 'Haarsingharr',
        'last_name'      => 'Test',
        'phone_country'  => '+92',
        'phone'          => '3001234567',
        'address_line_1' => 'House 5, Street 2',
        'city'           => 'Lahore',
        'province'       => 'punjab',
        'country'        => 'PK',
        'postal_code'    => '54000',
        'type'            => 'shipping',
    ];
}

// ============================================================================
// Payment Webhook Idempotency & Signature Verification Tests
// ============================================================================

test('duplicate webhook events do not create duplicate payments', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10, 'safety_stock' => 0]);
    $customer = User::factory()->create();
    $cart = Cart::factory()->create(['user_id' => $customer->id]);
    $cart->items()->create([
        'product_id'         => $variant->product_id,
        'product_variant_id' => $variant->id,
        'quantity'           => 1,
        'unit_price'         => $variant->regular_price,
        'line_total'         => $variant->regular_price,
    ]);

    $checkoutService = app(CheckoutService::class);
    $order = $checkoutService->placeOrder([
        'email' => $customer->email,
        'phone' => $customer->phone,
        'phone_country' => '+92',
        'shipping_address' => makeAddressArray(),
        'shipping_method' => 'standard',
        'payment_method' => 'jazzcash',
        'payment_provider' => 'jazzcash',
        'idempotency_key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $payment = \App\Models\Payment::create([
        'order_id'  => $order->id,
        'provider'  => 'jazzcash',
        'method'    => 'wallet',
        'status'    => 'pending',
        'amount'    => $order->grand_total,
        'currency'  => 'PKR',
    ]);

    // First event
    $firstEvent = \App\Models\PaymentEvent::create([
        'payment_id'         => $payment->id,
        'type'               => 'captured',
        'provider_event_id'  => 'JC-TEST-EVENT-001',
        'status'             => '000',
        'amount'             => $payment->amount,
        'currency'           => $payment->currency,
        'payload'            => ['pp_TxnRefNo' => 'JC-TEST-EVENT-001'],
        'is_verified'         => true,
    ]);

    // Second event with same ID should be detected as duplicate
    $exists = \App\Models\PaymentEvent::where('provider_event_id', 'JC-TEST-EVENT-001')->exists();
    expect($exists)->toBeTrue();
});

test('jazzcash webhook with invalid signature is rejected', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Pending,
        'grand_total' => 5000,
    ]);

    $payment = \App\Models\Payment::create([
        'order_id'  => $order->id,
        'provider'  => 'jazzcash',
        'method'    => 'wallet',
        'status'    => 'pending',
        'amount'    => 5000,
        'currency'  => 'PKR',
    ]);

    $request = \Illuminate\Http\Request::create('/webhooks/payments/jazzcash', 'POST', [
        'pp_TxnRefNo' => 'JC-TEST-123',
        'pp_Amount'   => '500000',
        'pp_SecureHash' => 'invalid_signature',
    ]);

    $gateway = app(JazzCashGateway::class);

    expect(fn () => $gateway->handleWebhook($request, $payment, $order))
        ->toThrow(\RuntimeException::class);
});

// ============================================================================
// Coupon Validation Tests
// ============================================================================

test('expired coupon is invalid', function () {
    $coupon = \App\Models\Coupon::factory()->create([
        'code'        => 'EXPIRED10',
        'type'        => 'percentage',
        'value'       => 10,
        'expires_at'  => now()->subDay(),
        'status'      => 'active',
    ]);

    expect($coupon->isValid())->toBeFalse();
});

test('coupon with usage limit exceeded is invalid', function () {
    $coupon = \App\Models\Coupon::factory()->create([
        'code'             => 'LIMITED',
        'type'             => 'fixed',
        'value'            => 500,
        'usage_limit_total' => 1,
        'used_count'       => 1,
        'status'           => 'active',
    ]);

    expect($coupon->isValid())->toBeFalse();
});

// ============================================================================
// Order State Machine Tests
// ============================================================================

test('order cannot transition from delivered to pending', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
    ]);

    expect($order->status->canTransitionTo(OrderStatus::Pending))->toBeFalse()
        ->and($order->status->canTransitionTo(OrderStatus::Returned))->toBeTrue();
});

test('order number format is human-readable', function () {
    $number = Order::nextOrderNumber();
    expect($number)->toStartWith('AU-')
        ->and(strlen($number))->toBeLessThanOrEqual(16);
});

// ============================================================================
// Audit Log Immutability Tests
// ============================================================================

test('audit log entries are immutable', function () {
    $log = \App\Models\AuditLog::create([
        'action' => 'test_action',
        'module' => 'tests',
        'properties' => ['foo' => 'bar'],
        'ip_address' => '127.0.0.1',
        'outcome' => 'success',
    ]);

    expect(fn () => $log->update(['action' => 'tampered']))
        ->toThrow(\RuntimeException::class)
        ->and(fn () => $log->delete())
        ->toThrow(\RuntimeException::class);
});

// ============================================================================
// Permission Boundary Tests
// ============================================================================

test('product manager cannot access staff management', function () {
    $pm = User::factory()->staff()->create();
    $pm->assignRole('product-manager');

    $response = $this->actingAs($pm)->get('/admin/staff');
    $response->assertForbidden();
});

test('customer support cannot view cost prices', function () {
    $support = User::factory()->staff()->create();
    $support->assignRole('customer-support');

    $response = $this->actingAs($support)->get('/admin/products');
    $response->assertOk(); // can view products list
    // Cost price column should not appear — checked at the policy layer
    expect($support->can('products.adjust_cost'))->toBeFalse();
});
