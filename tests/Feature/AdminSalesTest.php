<?php

use App\Mail\OrderConfirmationMail;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Admin sales regression tests (Phase 3)
|--------------------------------------------------------------------------
|
| Covers order filtering/bulk actions, notes, addresses, partial fulfilment,
| manual payments, cancellations, refunds, exchanges and shipping operations.
|
*/

if (! function_exists('salesOrderWithItem')) {
    /**
     * @return array{0: Order, 1: OrderItem, 2: ProductVariant}
     */
    function salesOrderWithItem(array $orderAttributes = [], int $quantity = 1, int $stock = 10): array
    {
        $variant = ProductVariant::factory()->create([
            'stock_quantity' => $stock,
            'reserved_stock' => 0,
            'safety_stock' => 0,
        ]);

        $order = Order::factory()->create(array_merge([
            'subtotal' => 10000,
            'grand_total' => 10000,
            'shipping_total' => 0,
        ], $orderAttributes));

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Heritage Ring',
            'product_slug' => 'heritage-ring',
            'sku' => $variant->sku,
            'variant_title' => '18k · 16',
            'quantity' => $quantity,
            'unit_price' => 5000,
            'line_total' => 5000 * $quantity,
            'fulfilment_status' => 'unfulfilled',
        ]);

        return [$order, $item, $variant];
    }
}

if (! function_exists('consumeSaleStock')) {
    function consumeSaleStock(Order $order, ProductVariant $variant, int $quantity): void
    {
        $inventory = app(InventoryService::class);
        $reservation = $inventory->reserve($variant, $quantity, $order);
        $inventory->consume($reservation, $order);
    }
}

beforeEach(function () {
    $this->actingAs(createAdmin());
});

test('orders can be filtered by saved views and exported as CSV', function () {
    [$unpaid] = salesOrderWithItem([
        'guest_email' => 'unpaid@example.com',
        'payment_status' => 'unpaid',
        'status' => 'confirmed',
    ]);
    [$paid] = salesOrderWithItem([
        'guest_email' => 'paid@example.com',
        'payment_status' => 'paid',
        'status' => 'delivered',
    ]);

    $this->get(route('admin.orders.index', ['view' => 'unpaid']))
        ->assertOk()
        ->assertSee($unpaid->number)
        ->assertDontSee($paid->number);

    $this->get(route('admin.orders.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('bulk order actions update status and route to print and export', function () {
    [$first] = salesOrderWithItem(['status' => 'pending']);
    [$second] = salesOrderWithItem(['status' => 'pending']);

    $this->post(route('admin.orders.bulk'), [
        'ids' => [$first->id, $second->id],
        'action' => 'status',
        'status' => 'confirmed',
    ])->assertRedirect();

    expect($first->fresh()->status->value)->toBe('confirmed')
        ->and($second->fresh()->status->value)->toBe('confirmed');

    $this->post(route('admin.orders.bulk'), [
        'ids' => [$first->id],
        'action' => 'export',
    ])->assertRedirect(route('admin.orders.export', ['ids' => (string) $first->id]));

    $this->post(route('admin.orders.bulk'), [
        'ids' => [$first->id],
        'action' => 'print',
    ])->assertRedirect(route('admin.orders.print', ['ids' => (string) $first->id]));

    $this->get(route('admin.orders.print', ['ids' => (string) $first->id]))
        ->assertOk()
        ->assertSee($first->number);
});

test('order notes addresses and item fulfilment are editable', function () {
    [$order, $item] = salesOrderWithItem(['status' => 'processing']);

    $this->post(route('admin.orders.notes.store', $order), [
        'body' => 'Customer asked for gift wrapping.',
    ])->assertRedirect();

    $this->assertDatabaseHas('order_notes', [
        'order_id' => $order->id,
        'body' => 'Customer asked for gift wrapping.',
    ]);

    $this->put(route('admin.orders.addresses.update', [$order, 'shipping']), [
        'first_name' => 'Ayesha',
        'last_name' => 'Khan',
        'phone_country' => '+92',
        'phone' => '3001234567',
        'address_line_1' => '12 Gulberg Lane',
        'city' => 'Lahore',
        'province' => 'Punjab',
        'country' => 'PK',
    ])->assertRedirect();

    $shipping = OrderAddress::where('order_id', $order->id)->where('type', 'shipping')->firstOrFail();
    expect($shipping->city)->toBe('Lahore');

    $this->patch(route('admin.orders.items.fulfilment', [$order, $item->id]), [
        'fulfilment_status' => 'delivered',
    ])->assertRedirect();

    expect($item->fresh()->fulfilment_status)->toBe('delivered')
        ->and($order->fresh()->fulfilment_status)->toBe('fulfilled');
});

test('manual payments update the order balance and capture status', function () {
    [$order] = salesOrderWithItem(['grand_total' => 10000]);

    $this->post(route('admin.orders.payments.store', $order), [
        'provider' => 'bank_transfer',
        'method' => 'bank',
        'amount' => 4000,
        'reference' => 'BANK-001',
        'mark_paid' => '1',
    ])->assertRedirect();

    $payment = Payment::where('order_id', $order->id)->firstOrFail();
    expect($payment->status)->toBe('captured')
        ->and((float) $payment->amount_captured)->toBe(4000.0)
        ->and((float) $order->fresh()->paid_amount)->toBe(4000.0)
        ->and($order->fresh()->payment_status->value)->toBe('pending');

    $this->post(route('admin.orders.payments.store', $order), [
        'provider' => 'cod',
        'amount' => 6000,
        'mark_paid' => '1',
    ])->assertRedirect();

    expect((float) $order->fresh()->paid_amount)->toBe(10000.0)
        ->and($order->fresh()->payment_status->value)->toBe('paid');
});

test('cancelling an order restocks consumed stock exactly once', function () {
    [$order, $item, $variant] = salesOrderWithItem(['status' => 'processing'], 2, 10);

    consumeSaleStock($order, $variant, 2);
    expect((int) $variant->fresh()->stock_quantity)->toBe(8);

    $this->post(route('admin.orders.cancel', $order), [
        'reason' => 'Customer changed their mind.',
    ])->assertRedirect();

    expect($order->fresh()->status->value)->toBe('cancelled')
        ->and((int) $variant->fresh()->stock_quantity)->toBe(10);

    // A second cancellation must not inflate stock.
    $this->post(route('admin.orders.cancel', $order))->assertRedirect();
    expect((int) $variant->fresh()->stock_quantity)->toBe(10);
});

test('shipments sync tracking and drive order status transitions', function () {
    [$order] = salesOrderWithItem(['status' => 'packed']);

    $this->post(route('admin.orders.shipments.store', $order), [
        'courier' => 'tcs',
        'tracking_number' => 'TCS-555',
        'estimated_delivery_at' => now()->addDays(2)->toDateString(),
    ])->assertRedirect();

    $shipment = Shipment::where('order_id', $order->id)->firstOrFail();
    expect($order->fresh()->tracking_number)->toBe('TCS-555')
        ->and($order->fresh()->status->value)->toBe('shipped');

    $this->put(route('admin.orders.shipments.update', [$order, $shipment]), [
        'courier' => 'tcs',
        'tracking_number' => 'TCS-555',
        'status' => 'delivered',
    ])->assertRedirect();

    expect($shipment->fresh()->status)->toBe('delivered')
        ->and($order->fresh()->status->value)->toBe('delivered')
        ->and($order->fresh()->delivered_at)->not->toBeNull();

    $this->delete(route('admin.orders.shipments.destroy', [$order, $shipment]))->assertRedirect();
    expect($order->fresh()->tracking_number)->toBeNull();
});

test('payments can be captured or failed manually', function () {
    [$captureOrder] = salesOrderWithItem(['status' => 'pending']);
    $payment = Payment::create([
        'order_id' => $captureOrder->id,
        'provider' => 'cod',
        'method' => 'cash',
        'amount' => 10000,
        'currency' => 'PKR',
        'status' => 'pending',
    ]);

    $this->post(route('admin.payments.capture', $payment), [
        'reference' => 'COD-001',
        'note' => 'Cash received.',
    ])->assertRedirect();

    expect($payment->fresh()->status)->toBe('captured')
        ->and((float) $captureOrder->fresh()->paid_amount)->toBe(10000.0)
        ->and($captureOrder->fresh()->status->value)->toBe('confirmed');

    [$failOrder] = salesOrderWithItem(['status' => 'pending']);
    $failed = Payment::create([
        'order_id' => $failOrder->id,
        'provider' => 'jazzcash',
        'amount' => 10000,
        'currency' => 'PKR',
        'status' => 'pending',
    ]);

    $this->post(route('admin.payments.fail', $failed), [
        'reason' => 'The customer abandoned the wallet flow.',
    ])->assertRedirect();

    expect($failed->fresh()->status)->toBe('failed')
        ->and($failOrder->fresh()->payment_status->value)->toBe('failed');
});

test('a full refund completes, updates totals and restocks once', function () {
    [$order, $item, $variant] = salesOrderWithItem(['status' => 'processing'], 2, 10);
    consumeSaleStock($order, $variant, 2);

    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'cod',
        'method' => 'cash',
        'amount' => 10000,
        'amount_captured' => 10000,
        'currency' => 'PKR',
        'status' => 'captured',
    ]);

    $this->post(route('admin.refunds.store'), [
        'payment_id' => $payment->id,
        'amount' => 10000,
        'type' => 'full',
        'reason' => 'Returned in original condition.',
        'restock' => '1',
    ])->assertRedirect();

    $refund = Refund::where('payment_id', $payment->id)->firstOrFail();
    expect($refund->restock_items)->toBeTrue();

    $this->post(route('admin.refunds.approve', $refund))->assertRedirect();

    expect($refund->fresh()->status)->toBe('completed')
        ->and((float) $order->fresh()->refunded_amount)->toBe(10000.0)
        ->and($order->fresh()->payment_status->value)->toBe('refunded')
        ->and($payment->fresh()->status)->toBe('refunded')
        ->and($item->fresh()->refunded_quantity)->toBe(2)
        ->and((int) $variant->fresh()->stock_quantity)->toBe(10)
        ->and($refund->fresh()->restocked_at)->not->toBeNull();

    $this->get(route('admin.refunds.receipt', $refund))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('refunds can be rejected or marked completed after settlement', function () {
    [$order] = salesOrderWithItem(['grand_total' => 5000]);
    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'jazzcash',
        'amount' => 5000,
        'amount_captured' => 5000,
        'currency' => 'PKR',
        'status' => 'captured',
    ]);

    $this->post(route('admin.refunds.store'), [
        'payment_id' => $payment->id,
        'amount' => 2000,
        'type' => 'partial',
        'reason' => 'Small repair credit.',
    ])->assertRedirect();

    $rejected = Refund::where('payment_id', $payment->id)->firstOrFail();
    $this->post(route('admin.refunds.reject', $rejected), [
        'note' => 'Outside the return window.',
    ])->assertRedirect();
    expect($rejected->fresh()->status)->toBe('cancelled');

    $this->post(route('admin.refunds.store'), [
        'payment_id' => $payment->id,
        'amount' => 2000,
        'type' => 'partial',
        'reason' => 'Repair credit after review.',
    ])->assertRedirect();

    $processing = Refund::where('payment_id', $payment->id)->where('status', 'pending')->firstOrFail();
    $processing->update(['status' => 'processing']);

    $this->post(route('admin.refunds.complete', $processing), [
        'reference' => 'JC-SETTLE-1',
        'note' => 'Wallet settlement confirmed.',
    ])->assertRedirect();

    expect($processing->fresh()->status)->toBe('completed')
        ->and((float) $order->fresh()->refunded_amount)->toBe(2000.0)
        ->and($order->fresh()->payment_status->value)->toBe('partially_refunded');
});

test('returns can be created received with restock and completed', function () {
    [$order, $item, $variant] = salesOrderWithItem([], 2, 5);

    $this->post(route('admin.exchanges.store'), [
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'type' => 'return',
        'quantity' => 2,
        'reason' => 'The ring is too small.',
    ])->assertRedirect();

    $exchange = ExchangeRequest::where('order_id', $order->id)->firstOrFail();
    expect($exchange->status)->toBe('pending');

    $this->post(route('admin.exchanges.receive', $exchange), [
        'restock' => '1',
        'note' => 'Received at the Lahore studio.',
    ])->assertRedirect();

    expect($exchange->fresh()->received_at)->not->toBeNull()
        ->and($exchange->fresh()->restocked_at)->not->toBeNull()
        ->and((int) $variant->fresh()->stock_quantity)->toBe(7);

    $this->post(route('admin.exchanges.complete', $exchange))->assertRedirect();
    expect($exchange->fresh()->status)->toBe('completed')
        ->and($exchange->fresh()->completed_at)->not->toBeNull();

    $this->get(route('admin.exchanges.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('shipping zones rates labels and exports work', function () {
    $this->post(route('admin.shipping.zones.store'), [
        'name' => 'Punjab',
        'countries' => 'PK',
        'is_active' => '1',
    ])->assertRedirect();

    $zone = ShippingZone::where('name', 'Punjab')->firstOrFail();
    expect($zone->countries)->toBe(['PK']);

    $this->post(route('admin.shipping.rates.store', $zone), [
        'name' => 'Standard delivery',
        'method' => 'standard',
        'rate' => 350,
        'free_over' => 10000,
        'eta_min_days' => 2,
        'eta_max_days' => 4,
        'is_active' => '1',
    ])->assertRedirect();

    $rate = ShippingRate::where('shipping_zone_id', $zone->id)->firstOrFail();
    expect((float) $rate->rate)->toBe(350.0)
        ->and($rate->etaLabel())->toBe('2–4 days');

    [$order] = salesOrderWithItem([], 1, 5);
    OrderAddress::create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'first_name' => 'Ayesha',
        'last_name' => 'Khan',
        'phone_country' => '+92',
        'phone' => '3001234567',
        'address_line_1' => '12 Gulberg Lane',
        'city' => 'Lahore',
        'province' => 'Punjab',
        'country' => 'PK',
    ]);
    $shipment = Shipment::create([
        'order_id' => $order->id,
        'courier' => 'tcs',
        'method' => 'standard',
        'tracking_number' => 'TCS-LABEL-1',
        'status' => 'in_transit',
    ]);

    $this->get(route('admin.shipping.shipments.label', $shipment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('admin.shipping.index'))->assertOk()->assertSee('Punjab');
    $this->get(route('admin.payments.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $this->get(route('admin.refunds.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('order confirmation emails can be resent', function () {
    Mail::fake();

    [$order] = salesOrderWithItem(['guest_email' => 'buyer@example.com']);

    $this->post(route('admin.orders.emails.send', $order), [
        'type' => 'confirmation',
    ])->assertRedirect();

    Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('buyer@example.com'));
});
