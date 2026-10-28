<?php

use App\Mail\ReportEmailMail;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Shipment;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Admin report tests (Phase 6)
|--------------------------------------------------------------------------
|
| Pages for the 7 new report types, compare-period deltas, CSV + Excel
| exports, the "email me this report" action and the scheduled weekly
| digest for super-admins.
|
*/

/**
 * An order + item (+ optional payment / refund / shipment / address) fixture
 * inside the reporting window.
 */
if (! function_exists('reportSale')) {
    function reportSale(array $orderAttributes = [], int $quantity = 2): array
    {
        $order = Order::factory()->create(array_merge([
            'subtotal' => 10000,
            'grand_total' => 11000,
            'shipping_total' => 400,
            'tax_total' => 100,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'created_at' => now()->subDays(2),
        ], $orderAttributes));

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Heritage Ring',
            'product_slug' => 'heritage-ring',
            'sku' => 'HR-18K-16',
            'quantity' => $quantity,
            'unit_price' => 5000,
            'unit_cost' => 3000,
            'line_total' => 5000 * $quantity,
            'tax_amount' => 100,
            'discount_amount' => 0,
            'fulfilment_status' => 'shipped',
        ]);

        return [$order, $item];
    }
}

beforeEach(function () {
    $this->actingAs(createAdmin());
});

test('all seven new report pages render', function (string $route, string $tabLabel) {
    $this->get(route($route))->assertOk()->assertSee($tabLabel);
})->with([
    'products'   => ['admin.reports.products', 'Products Report'],
    'coupons'    => ['admin.reports.coupons', 'Coupons Report'],
    'tax'        => ['admin.reports.tax', 'Tax Report'],
    'shipping'   => ['admin.reports.shipping', 'Shipping Report'],
    'payments'   => ['admin.reports.payments', 'Payments'],
    'refunds'    => ['admin.reports.refunds', 'Refunds Report'],
    'exchanges'  => ['admin.reports.exchanges', 'Exchanges'],
]);

test('product report aggregates units, revenue and margin', function () {
    reportSale();

    $this->get(route('admin.reports.products'))
        ->assertOk()
        ->assertSee('Heritage Ring')
        ->assertSee('Rs. 10,000'); // 2 × 5000 line total
});

test('product revenue chart renders an SVG with a table fallback', function () {
    reportSale();

    $this->get(route('admin.reports.products'))
        ->assertOk()
        ->assertSee('<svg', false)
        ->assertSee('View chart as table', false);
});

test('sales report shows compare deltas for the previous period', function () {
    // Current-period sale plus one in the previous equivalent window.
    reportSale();
    reportSale(['created_at' => now()->subDays(35), 'status' => 'delivered']);

    $this->get(route('admin.reports.sales', ['compare' => 1]))
        ->assertOk()
        ->assertSee('% vs previous', false);
});

test('tax report breaks down collected tax by province', function () {
    [$order] = reportSale(['tax_total' => 500]);

    OrderAddress::create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'first_name' => 'Z', 'last_name' => 'Q', 'phone' => '+923000000000',
        'address_line_1' => 'House 1', 'city' => 'Karachi', 'province' => 'Sindh',
        'country' => 'PK',
    ]);

    $this->get(route('admin.reports.tax'))
        ->assertOk()
        ->assertSee('Sindh')
        ->assertSee('Rs. 500');
});

test('payments report computes provider success rates', function () {
    [$order] = reportSale();

    Payment::create([
        'order_id' => $order->id,
        'provider' => 'jazzcash',
        'method' => 'wallet',
        'status' => 'captured',
        'amount' => 11000,
        'amount_captured' => 11000,
    ]);

    Payment::create([
        'order_id' => Order::factory()->create(['created_at' => now()->subDays(2)])->id,
        'provider' => 'jazzcash',
        'status' => 'failed',
        'amount' => 5000,
        'amount_captured' => 0,
    ]);

    $this->get(route('admin.reports.payments'))
        ->assertOk()
        ->assertSee('Jazzcash')
        ->assertSee('50%');
});

test('coupons report ranks discount spend per coupon', function () {
    [$order] = reportSale(['coupon_code' => 'EID10', 'coupon_discount' => 1000]);

    $coupon = Coupon::create([
        'code' => 'EID10',
        'type' => 'percentage', 'value' => 10,
        'min_order_value' => 0, 'used_count' => 0,
        'status' => 'active',
    ]);

    CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'discount_applied' => 'EID10',
        'amount' => 1000,
    ]);

    $this->get(route('admin.reports.coupons'))
        ->assertOk()
        ->assertSee('EID10')
        ->assertSee('Rs. 1,000');
});

test('refunds and exchanges reports list their funnels', function () {
    [$order, $item] = reportSale();

    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'cod',
        'status' => 'captured',
        'amount' => 11000,
        'amount_captured' => 11000,
    ]);

    Refund::create([
        'payment_id' => $payment->id,
        'order_id' => $order->id,
        'amount' => 2000,
        'type' => 'partial',
        'status' => 'completed',
        'reason' => 'customer changed mind about plating',
    ]);

    ExchangeRequest::create([
        'number' => 'EX-101',
        'order_id' => $order->id,
        'order_item_id' => $item->id,
        'type' => 'exchange',
        'status' => 'pending',
        'reason' => 'wanted a different ring size',
        'quantity' => 1,
    ]);

    $this->get(route('admin.reports.refunds'))
        ->assertOk()
        ->assertSee('customer changed mind about plating')
        ->assertSee('Rs. 2,000');

    $this->get(route('admin.reports.exchanges'))
        ->assertOk()
        ->assertSee('EX-101')
        ->assertSee('wanted a different ring size');
});

test('shipping report ranks couriers and destinations', function () {
    [$order] = reportSale(['shipping_total' => 400]);

    Shipment::create([
        'order_id' => $order->id,
        'courier' => 'TCS',
        'tracking_number' => 'TCS-001',
        'status' => 'delivered',
        'shipping_cost' => 400,
        'shipped_at' => now(),
        'delivered_at' => now(),
    ]);

    OrderAddress::create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'first_name' => 'Z', 'last_name' => 'Q', 'phone' => '+923000000000',
        'address_line_1' => 'House 1', 'city' => 'Karachi', 'province' => 'Sindh',
        'country' => 'PK',
    ]);

    $this->get(route('admin.reports.shipping'))
        ->assertOk()
        ->assertSee('TCS')
        ->assertSee('Karachi');
});

test('csv and excel exports respond for every new report type', function (string $type, string $firstColumnHeader) {
    [$order] = reportSale();

    // Give row-building closures data (esp. the payments provider rows).
    $payment = Payment::create([
        'order_id' => $order->id,
        'provider' => 'jazzcash', 'method' => 'wallet',
        'status' => 'captured', 'amount' => 11000, 'amount_captured' => 11000,
    ]);
    Refund::create([
        'payment_id' => $payment->id, 'order_id' => $order->id,
        'amount' => 1000, 'type' => 'partial', 'status' => 'completed', 'reason' => 'export fixture',
    ]);

    $csv = $this->get(route('admin.reports.export', ['type' => $type, 'format' => 'csv']));
    // Streamed download bodies are not captured by the test client; the
    // charset suffix differs per stack (see AdminSalesTest precedent).
    expect((string) $csv->headers->get('Content-Type'))->toContain('text/csv');
    expect($csv->headers->get('Content-Disposition'))->toContain('.csv');

    $xlsx = $this->get(route('admin.reports.export', ['type' => $type, 'format' => 'xlsx']))->assertOk();
    expect((string) $xlsx->headers->get('Content-Type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
})->with([
    'products'  => ['products', 'Product'],
    'coupons'   => ['coupons', 'Coupon'],
    'tax'       => ['tax', 'Province'],
    'shipping'  => ['shipping', 'Courier'],
    'payments'  => ['payments', 'Provider'],
    'refunds'   => ['refunds', 'When'],
    'exchanges' => ['exchanges', 'When'],
]);

test('staff can email the current report to themselves', function () {
    Mail::fake();
    [$order] = reportSale();

    $admin = auth()->user();

    $this->post(route('admin.reports.email', ['type' => 'sales', 'from'=> now()->startOfMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')]))
        ->assertRedirect();

    Mail::assertQueued(ReportEmailMail::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email) && $mail->attachments() !== [];
    });
});

test('the weekly digest emails the sales xlsx to every super-admin', function () {
    Mail::fake();

    reportSale();
    reportSale(['created_at' => now()->subDays(30)]); // outside the 7-day window

    $admin = auth()->user();
    expect($admin->isSuperAdmin())->toBeTrue();

    $this->artisan('haarsingharr:weekly-report')->assertExitCode(0);

    Mail::assertSent(ReportEmailMail::class, 1); // exactly the seeded super-admin
    Mail::assertSent(ReportEmailMail::class, fn ($mail) => $mail->type === 'sales'
        && $mail->attachments() !== []);
});
