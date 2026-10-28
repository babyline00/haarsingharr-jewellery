<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\GiftClaim;
use App\Models\GiftPopupEvent;
use App\Models\Giveaway;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Notifications\GiftWonNotification;
use App\Services\CheckoutService;
use App\Services\GiftCouponService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Gift popup, unique coupons and giveaway draws
|--------------------------------------------------------------------------
|
| These tests cover the marketing surface added on top of the coupon system:
| server-issued single-use codes, popup analytics, and the admin draw.
|
*/

beforeEach(function () {
    foreach ([
        'gift_popup_enabled'      => '1',
        'gift_popup_mode'         => 'both',
        'gift_popup_prizes'       => "10% OFF|percentage|10|70\nFree shipping|free_shipping|0|30",
        'gift_popup_prize'        => '10% OFF',
        'gift_popup_coupon'       => 'WELCOME10',
        'gift_unique_coupons'     => '1',
        'gift_coupon_prefix'      => 'TEST',
        'gift_coupon_type'        => 'percentage',
        'gift_coupon_value'       => 10,
        'gift_coupon_min_order'   => 0,
        'gift_coupon_expiry_days' => 30,
    ] as $key => $value) {
        StoreSetting::set($key, $value, 'marketing');
    }
});

test('gift claim issues a unique single-use coupon', function () {
    $response = $this->postJson(route('gift.claim'), ['mode' => 'spin']);

    $response->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('already_claimed', false)
        ->assertJsonStructure(['prize', 'code', 'discount', 'expires_at']);

    $claim = GiftClaim::with('coupon')->firstOrFail();

    expect($claim->coupon)->not->toBeNull()
        ->and($claim->coupon->code)->toStartWith('TEST-')
        ->and($claim->coupon->usage_limit_total)->toBe(1)
        ->and($claim->coupon->usage_limit_per_customer)->toBe(1)
        ->and((float) $claim->coupon->value)->toBe(10.0)
        ->and($claim->mode)->toBe('spin');

    expect(GiftPopupEvent::where('event', 'claim')->count())->toBe(1);
});

test('gift claim is idempotent for the same session', function () {
    $service = app(GiftCouponService::class);
    $context = [
        'session_id' => 'test-gift-session',
        'mode'       => 'scratch',
        'ip_address' => '127.0.0.1',
    ];

    $first = $service->claim($context);
    $second = $service->claim($context);

    expect($second['already_claimed'])->toBeTrue()
        ->and($second['claim']->id)->toBe($first['claim']->id)
        ->and($second['code'])->toBe($first['code']);

    expect(GiftClaim::count())->toBe(1)
        ->and(Coupon::count())->toBe(1)
        ->and(GiftPopupEvent::where('event', 'claim')->count())->toBe(1);
});

test('gift claim is unavailable when the popup is disabled', function () {
    StoreSetting::set('gift_popup_enabled', '0', 'marketing');

    $this->postJson(route('gift.claim'))->assertNotFound();
});

test('popup impressions are recorded for analytics', function () {
    $this->postJson(route('gift.event'), ['event' => 'impression', 'mode' => 'spin'])
        ->assertOk();

    expect(GiftPopupEvent::where('event', 'impression')
        ->where('mode', 'spin')->exists())->toBeTrue();
});

test('admin giveaway draw issues coupons and notifies winners', function () {
    Notification::fake();

    $admin = createAdmin();
    $customers = collect(range(1, 4))->map(fn () => createCustomer());

    $giveaway = Giveaway::create([
        'name'               => 'Eid Gift Draw',
        'prize'              => '10% OFF — Eid Gift',
        'coupon_type'        => 'percentage',
        'coupon_value'       => 10,
        'coupon_expiry_days' => 30,
        'winners_count'      => 2,
        'status'             => 'open',
        'created_by'         => $admin->id,
    ]);

    foreach ($customers as $customer) {
        $giveaway->entries()->create([
            'user_id'  => $customer->id,
            'added_by' => $admin->id,
        ]);
    }

    $this->actingAs($admin)
        ->post(route('admin.giveaways.draw', $giveaway))
        ->assertRedirect();

    $giveaway->refresh();

    expect($giveaway->status)->toBe('drawn')
        ->and($giveaway->drawn_at)->not->toBeNull()
        ->and($giveaway->winners()->count())->toBe(2)
        ->and(GiftClaim::where('mode', 'giveaway')->count())->toBe(2);

    $winners = $giveaway->winners()->with(['user', 'claim.coupon'])->get();

    expect($winners)->toHaveCount(2);

    foreach ($winners as $winner) {
        expect($winner->gift_claim_id)->not->toBeNull()
            ->and($winner->claim->coupon)->not->toBeNull()
            ->and($winner->claim->coupon->usage_limit_total)->toBe(1);

        Notification::assertSentTo($winner->user, GiftWonNotification::class);
    }

    $this->actingAs($admin)
        ->get(route('admin.giveaways.index'))
        ->assertOk()
        ->assertSee('2 / 2');

    $this->get(route('admin.giveaways.show', $giveaway))
        ->assertOk()
        ->assertSee('Winners');
});

test('admin gift and giveaway pages render', function () {
    $admin = createAdmin();

    GiftClaim::create([
        'prize'      => '10% OFF',
        'mode'       => 'scratch',
        'status'     => 'issued',
        'claimed_at' => now(),
    ]);

    $giveaway = Giveaway::create([
        'name'               => 'Anniversary Draw',
        'prize'              => 'Free shipping',
        'coupon_type'        => 'free_shipping',
        'coupon_value'       => 0,
        'coupon_expiry_days' => 14,
        'winners_count'      => 1,
        'status'             => 'open',
        'created_by'         => $admin->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.gifts.index'))
        ->assertOk()
        ->assertSee('Gift Popup');

    $this->get(route('admin.giveaways.index'))
        ->assertOk()
        ->assertSee('Giveaways');

    $this->get(route('admin.giveaways.create'))
        ->assertOk()
        ->assertSee('New Giveaway');

    $this->get(route('admin.giveaways.show', $giveaway))
        ->assertOk()
        ->assertSee('Anniversary Draw');
});

test('checkout consumes a unique gift coupon and marks the claim redeemed', function () {
    $variant = ProductVariant::factory()->create([
        'regular_price'  => 10000,
        'stock_quantity' => 5,
        'safety_stock'   => 0,
    ]);

    $customer = createCustomer();

    $coupon = Coupon::factory()->create([
        'code'                     => 'TEST-CHECKOUT',
        'type'                     => 'percentage',
        'value'                    => 10,
        'min_order_value'          => 0,
        'usage_limit_total'        => 1,
        'usage_limit_per_customer' => 1,
        'used_count'               => 0,
        'status'                   => 'active',
        'starts_at'                => now()->subDay(),
        'expires_at'               => now()->addMonth(),
    ]);

    $claim = GiftClaim::create([
        'user_id'    => $customer->id,
        'coupon_id'  => $coupon->id,
        'prize'      => '10% OFF',
        'mode'       => 'scratch',
        'status'     => 'issued',
        'claimed_at' => now(),
    ]);

    $cart = Cart::factory()->create([
        'user_id'     => $customer->id,
        'coupon_id'   => $coupon->id,
        'coupon_code' => $coupon->code,
        'subtotal'    => 10000,
        'discount'    => 1000,
        'shipping'    => 0,
        'tax'         => 0,
        'total'       => 9000,
    ]);

    $cart->items()->create([
        'product_id'         => $variant->product_id,
        'product_variant_id' => $variant->id,
        'quantity'           => 1,
        'unit_price'         => 10000,
        'line_total'         => 10000,
    ]);

    $this->actingAs($customer);

    $order = app(CheckoutService::class)->placeOrder([
        'email'            => $customer->email,
        'phone'            => $customer->phone,
        'phone_country'    => '+92',
        'shipping_address' => giftAddressArray(),
        'shipping_method'  => 'standard',
        'payment_method'   => 'cod',
        'payment_provider' => 'cod',
        'idempotency_key'  => (string) Str::uuid(),
    ]);

    expect($order->coupon_id)->toBe($coupon->id)
        ->and($order->coupon_code)->toBe($coupon->code)
        ->and((int) $coupon->fresh()->used_count)->toBe(1)
        ->and($claim->fresh()->status)->toBe('redeemed')
        ->and($claim->fresh()->redeemed_at)->not->toBeNull();
});

function giftAddressArray(): array
{
    return [
        'first_name'     => 'Gift',
        'last_name'      => 'Tester',
        'phone_country'  => '+92',
        'phone'          => '3001234567',
        'address_line_1' => 'House 5, Street 2',
        'city'           => 'Lahore',
        'province'       => 'punjab',
        'country'        => 'PK',
        'postal_code'    => '54000',
        'type'           => 'shipping',
    ];
}
