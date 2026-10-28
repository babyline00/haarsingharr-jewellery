<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Mail\ReportEmailMail;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddress;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Shipment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
=======
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
>>>>>>> origin/main
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
<<<<<<< HEAD
    /** Report slugs manageable through the shared export/email endpoints. */
    public const TYPES = [
        'sales', 'inventory', 'customers', 'staff-activity',
        'products', 'coupons', 'tax', 'shipping', 'payments', 'refunds', 'exchanges',
    ];

    public function __construct(private readonly AuditService $audit) {}

=======
>>>>>>> origin/main
    /**
     * Resolve the reporting window from the request (defaults to this month).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->input('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->input('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }

<<<<<<< HEAD
    /**
     * Previous window of identical length, immediately before the current one —
     * used by the "compare" toggle.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function compareWindow(Carbon $from, Carbon $to): array
    {
        $shift = (int) $from->diffInDays($to) + 1;

        return [
            (clone $from)->subDays($shift),
            (clone $to)->subDays($shift),
        ];
    }

    /**
     * Per-key delta between two summary arrays: percentage text + trend.
     */
    private function deltas(array $current, array $previous): array
    {
        $out = [];

        foreach ($current as $key => $value) {
            $value = (float) $value;
            $prev = (float) ($previous[$key] ?? 0);

            if ($prev == 0.0) {
                $out[$key] = [
                    'text'  => $value > 0 ? 'new this period' : 'no change',
                    'trend' => $value > 0 ? 'up' : 'flat',
                ];
                continue;
            }

            $pct = round((($value - $prev) / abs($prev)) * 100, 1);

            $out[$key] = [
                'text'  => ($pct >= 0 ? '+' : '').number_format($pct, 1).'% vs previous',
                'trend' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'),
            ];
        }

        return $out;
    }

    /** Daily series of orders revenue within a window. */
    private function dailyRevenue(Carbon $from, Carbon $to, string $column = 'grand_total'): array
    {
        $rows = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day, sum('.$column.') as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return [
            'labels' => $rows->map(fn ($r) => Carbon::parse($r->day)->format('M j'))->all(),
            'points' => array_map('floatval', $rows->pluck('total')->all()),
        ];
    }

    // -----------------------------------------------------------------------
    // Existing reports
    // -----------------------------------------------------------------------

    public function sales(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $summary = fn (Carbon $f, Carbon $t) => [
            'orders'   => (float) Order::whereBetween('created_at', [$f, $t])->count(),
            'revenue'  => (float) Order::whereBetween('created_at', [$f, $t])->sum('grand_total'),
            'paid'     => (float) Order::whereBetween('created_at', [$f, $t])->sum('paid_amount'),
            'refunded' => (float) Order::whereBetween('created_at', [$f, $t])->sum('refunded_amount'),
            'average'  => (float) Order::whereBetween('created_at', [$f, $t])->avg('grand_total'),
        ];
        $current = $summary($from, $to);
=======
    public function sales(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $base = Order::whereBetween('created_at', [$from, $to]);

        $summary = [
            'orders'      => (clone $base)->count(),
            'revenue'     => (float) (clone $base)->sum('grand_total'),
            'paid'        => (float) (clone $base)->sum('paid_amount'),
            'refunded'    => (float) (clone $base)->sum('refunded_amount'),
            'average'     => (float) (clone $base)->avg('grand_total'),
        ];

        $daily = (clone $base)
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as orders'), DB::raw('sum(grand_total) as revenue'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();
>>>>>>> origin/main

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'order_items.product_name',
                DB::raw('sum(order_items.quantity) as units'),
                DB::raw('sum(order_items.line_total) as revenue'),
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

<<<<<<< HEAD
        $byStatus = Order::whereBetween('created_at', [$from, $to])
=======
        $byStatus = (clone $base)
>>>>>>> origin/main
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $byProvider = Payment::whereBetween('created_at', [$from, $to])
            ->select('provider', DB::raw('count(*) as total'), DB::raw('sum(amount) as amount'))
            ->groupBy('provider')
            ->get();

<<<<<<< HEAD
        $series = $this->dailyRevenue($from, $to);
        $previousSeries = $compare ? $this->dailyRevenue($cFrom, $cTo) : null;

        $daily = Order::whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day, count(*) as orders, sum(grand_total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.reports.sales', [
            'from' => $from, 'to' => $to,
            'compare'      => $compare,
            'compareFrom'  => $cFrom, 'compareTo' => $cTo,
            'summary'      => $current,
            'deltas'       => $compare ? $this->deltas($current, $summary($cFrom, $cTo)) : [],
            'chart'        => $series,
            'prevChart'    => $previousSeries,
            'daily'        => $daily,
            'topProducts'  => $topProducts,
            'byStatus'     => $byStatus,
            'byProvider'   => $byProvider,
=======
        return view('admin.reports.sales', [
            'from' => $from, 'to' => $to,
            'summary' => $summary,
            'daily' => $daily,
            'topProducts' => $topProducts,
            'byStatus' => $byStatus,
            'byProvider' => $byProvider,
>>>>>>> origin/main
        ]);
    }

    public function inventory(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $variants = ProductVariant::with('product');

        $summary = [
            'variants'   => (clone $variants)->count(),
            'units'      => (int) (clone $variants)->sum('stock_quantity'),
<<<<<<< HEAD
            'value'      => (float) (clone $variants)->sum(DB::raw('stock_quantity * COALESCE(cost_price, 0)')),
=======
            'value'      => (float) (clone $variants)->sum(DB::raw('stock_quantity * cost_price')),
>>>>>>> origin/main
            'low_stock'  => (clone $variants)->whereColumn('stock_quantity', '<=', 'COALESCE(low_stock_threshold, 5)')->whereColumn('stock_quantity', '>', 'reserved_stock')->count(),
            'out_stock'  => (clone $variants)->whereColumn('stock_quantity', '<=', 'reserved_stock')->count(),
        ];

        $lowStock = ProductVariant::with('product')
            ->whereColumn('stock_quantity', '<=', 'COALESCE(low_stock_threshold, 5)')
            ->orderBy('stock_quantity')
            ->limit(50)
            ->get();

        $movements = DB::table('inventory_movements')
            ->whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('count(*) as total'), DB::raw('sum(quantity_change) as net'))
            ->groupBy('type')
            ->get();

        return view('admin.reports.inventory', [
            'from' => $from, 'to' => $to,
            'summary' => $summary,
            'lowStock' => $lowStock,
            'movements' => $movements,
        ]);
    }

    public function customers(Request $request): View
    {
        [$from, $to] = $this->period($request);
<<<<<<< HEAD
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');
=======
>>>>>>> origin/main

        $newCustomers = User::customers()->whereBetween('created_at', [$from, $to])->count();

        $daily = User::customers()
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topCustomers = User::customers()
            ->withCount(['orders'])
            ->withSum('orders', 'grand_total')
            ->orderByDesc('orders_sum_grand_total')
            ->limit(10)
            ->get();

        $summary = [
            'total'        => User::customers()->count(),
            'new'          => $newCustomers,
            'with_orders'  => User::customers()->has('orders')->count(),
            'marketing'    => User::customers()->where('marketing_consent', true)->count(),
        ];

<<<<<<< HEAD
        $summaryFor = fn (Carbon $f, Carbon $t) => [
            'new' => (float) User::customers()->whereBetween('created_at', [$f, $t])->count(),
        ];

        return view('admin.reports.customers', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas(['new' => $newCustomers], $summaryFor($cFrom, $cTo)) : [],
            'daily'        => $daily,
=======
        return view('admin.reports.customers', [
            'from' => $from, 'to' => $to,
            'summary' => $summary,
            'daily' => $daily,
>>>>>>> origin/main
            'topCustomers' => $topCustomers,
        ]);
    }

    public function staffActivity(Request $request): View
    {
        [$from, $to] = $this->period($request);
<<<<<<< HEAD
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $logs = AuditLog::whereBetween('created_at', [$from, $to]);
        $total = (clone $logs)->count();
=======

        $logs = AuditLog::whereBetween('created_at', [$from, $to]);
>>>>>>> origin/main

        $byActor = (clone $logs)
            ->select('actor_id', 'actor_email', DB::raw('count(*) as total'))
            ->groupBy('actor_id', 'actor_email')
            ->orderByDesc('total')
            ->limit(25)
            ->get();

        $byAction = (clone $logs)
            ->select('action', DB::raw('count(*) as total'))
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(25)
            ->get();

        $recent = AuditLog::with('actor')->latest()->limit(50)->get();

<<<<<<< HEAD
        $previous = AuditLog::whereBetween('created_at', [$cFrom, $cTo])->count();

        return view('admin.reports.staff-activity', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'total'   => $total,
            'deltas'  => $compare ? $this->deltas(['total' => $total], ['total' => $previous]) : [],
=======
        return view('admin.reports.staff-activity', [
            'from' => $from, 'to' => $to,
            'total' => (clone $logs)->count(),
>>>>>>> origin/main
            'byActor' => $byActor,
            'byAction' => $byAction,
            'recent' => $recent,
        ]);
    }

<<<<<<< HEAD
    // -----------------------------------------------------------------------
    // Phase 6 reports
    // -----------------------------------------------------------------------

    public function products(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'revenue'  => (float) OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', $this->sellingStatuses())
                ->whereBetween('orders.created_at', [$f, $t])
                ->sum('order_items.line_total'),
            'units'    => (float) OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', $this->sellingStatuses())
                ->whereBetween('orders.created_at', [$f, $t])
                ->sum('order_items.quantity'),
            'profit'   => (float) OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', $this->sellingStatuses())
                ->whereBetween('orders.created_at', [$f, $t])
                ->selectRaw('sum(order_items.line_total - order_items.quantity * COALESCE(order_items.unit_cost, 0)) as profit')
                ->value('profit'),
            'discount' => (float) OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', $this->sellingStatuses())
                ->whereBetween('orders.created_at', [$f, $t])
                ->sum('order_items.discount_amount'),
        ];
        $current = $agg($from, $to);

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $this->sellingStatuses())
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'order_items.product_name',
                'order_items.product_slug',
                DB::raw('sum(order_items.quantity) as units'),
                DB::raw('sum(order_items.line_total) as revenue'),
                DB::raw('sum(order_items.discount_amount) as discount'),
                DB::raw('sum(order_items.quantity * COALESCE(order_items.unit_cost, 0)) as cost'),
                DB::raw('count(distinct order_items.order_id) as orders'),
            )
            ->groupBy('order_items.product_name', 'order_items.product_slug')
            ->orderByDesc('revenue')
            ->limit(25)
            ->get();

        $summary = [
            'revenue'  => $current['revenue'],
            'units'    => $current['units'],
            'profit'   => $current['profit'],
            'discount' => $current['discount'],
        ];

        $chart = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', $this->sellingStatuses())
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('date(orders.created_at) as day, sum(order_items.line_total) as total')
            ->groupBy('day')->orderBy('day')
            ->get()
            ->map(fn ($r) => [
                'label' => Carbon::parse($r->day)->format('M j'),
                'value' => (float) $r->total,
            ]);
        $chartLabels = $chart->map(fn ($r) => $r['label'])->all();
        $chartPoints = $chart->map(fn ($r) => $r['value'])->all();
        $chart = ['labels' => $chartLabels, 'revenue' => $chartPoints];

        return view('admin.reports.products', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas($summary, $agg($cFrom, $cTo)) : [],
            'rows'        => $rows,
            'chart'       => $chart,
        ]);
    }

    public function coupons(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'redemptions' => (float) CouponRedemption::whereBetween('created_at', [$f, $t])->count(),
            'given'       => (float) CouponRedemption::whereBetween('created_at', [$f, $t])->sum('amount'),
            'orders'      => (float) Order::whereBetween('created_at', [$f, $t])->whereNotNull('coupon_id')->count(),
            'revenue'     => (float) Order::whereBetween('created_at', [$f, $t])->whereNotNull('coupon_id')->sum('grand_total'),
        ];
        $current = $agg($from, $to);
        $previous = $compare ? $agg($cFrom, $cTo) : [];

        $summary = [
            'redemptions'    => $current['redemptions'],
            'given'          => $current['given'],
            'coupon_revenue' => $current['revenue'],
            'avg_order'      => $current['orders'] > 0 ? $current['revenue'] / $current['orders'] : 0.0,
        ];
        $prevSummary = [
            'redemptions'    => $previous['redemptions'] ?? 0,
            'given'          => $previous['given'] ?? 0,
            'coupon_revenue' => $previous['revenue'] ?? 0,
            'avg_order'      => (($previous['orders'] ?? 0) > 0
                ? ($previous['revenue'] ?? 0) / $previous['orders'] : 0.0),
        ];

        $rows = Coupon::withCount(['redemptions' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->get()
            ->map(function ($coupon) use ($from, $to) {
                $ordersIn = Order::where('coupon_id', $coupon->id)
                    ->whereBetween('created_at', [$from, $to]);
                $given = CouponRedemption::where('coupon_id', $coupon->id)
                    ->whereBetween('created_at', [$from, $to])->sum('amount');

                return (object) [
                    'code'        => $coupon->code,
                    'type'        => $coupon->type,
                    'value'       => $coupon->value,
                    'redemptions' => $coupon->redemptions_count,
                    'given'       => (float) $given,
                    'revenue'     => (float) $ordersIn->sum('grand_total'),
                    'avg_order'   => $ordersIn->count() > 0 ? (float) $ordersIn->avg('grand_total') : 0.0,
                ];
            })
            ->filter(fn ($r) => $r->redemptions > 0 || $r->given > 0)
            ->sortByDesc('given')
            ->values();

        $given = $this->dailyRevenue($from, $to, 'discount_total');

        return view('admin.reports.coupons', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas($summary, $prevSummary) : [],
            'rows'        => $rows,
            'chart'       => $given,
        ]);
    }

    public function tax(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'tax'      => (float) Order::whereIn('status', $this->sellingStatuses())
                ->whereBetween('created_at', [$f, $t])->sum('tax_total'),
            'orders'   => (float) Order::whereIn('status', $this->sellingStatuses())
                ->whereBetween('created_at', [$f, $t])->count(),
        ];
        $current = $agg($from, $to);

        $byProvince = OrderAddress::query()
            ->join('orders', 'orders.id', '=', 'order_addresses.order_id')
            ->whereIn('orders.status', $this->sellingStatuses())
            ->where('order_addresses.type', 'shipping')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'order_addresses.province',
                DB::raw('count(distinct orders.id) as orders'),
                DB::raw('sum(orders.tax_total) as tax'),
                DB::raw('sum(orders.grand_total) as revenue'),
            )
            ->groupBy('order_addresses.province')
            ->orderByDesc('tax')
            ->get();

        $summary = [
            'tax'    => $current['tax'],
            'orders' => $current['orders'],
            'avg_tax' => $current['orders'] > 0 ? $current['tax'] / $current['orders'] : 0.0,
        ];

        $chart = $this->dailyRevenue($from, $to, 'tax_total');

        return view('admin.reports.tax', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas(['tax' => $summary['tax']], $agg($cFrom, $cTo)) : [],
            'byProvince'  => $byProvince,
            'chart'       => $chart,
        ]);
    }

    public function shipping(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'revenue'    => (float) Order::whereIn('status', $this->sellingStatuses())
                ->whereBetween('created_at', [$f, $t])->sum('shipping_total'),
            'shipments'  => (float) Shipment::whereBetween('created_at', [$f, $t])->count(),
            'delivered'  => (float) Shipment::whereBetween('created_at', [$f, $t])->where('status', 'delivered')->count(),
        ];
        $current = $agg($from, $to);

        $byCourier = Shipment::query()
            ->whereBetween('created_at', [$from, $to])
            ->select(
                'courier',
                DB::raw('count(*) as shipments'),
                DB::raw("sum(case when status = 'delivered' then 1 else 0 end) as delivered"),
                DB::raw("sum(case when status in ('in_transit', 'out_for_delivery') then 1 else 0 end) as in_transit"),
                DB::raw("sum(case when status in ('failed', 'returned') then 1 else 0 end) as problem"),
                DB::raw('avg(shipping_cost) as avg_cost'),
            )
            ->groupBy('courier')
            ->orderByDesc('shipments')
            ->get();

        $byCity = OrderAddress::query()
            ->join('orders', 'orders.id', '=', 'order_addresses.order_id')
            ->whereIn('orders.status', $this->sellingStatuses())
            ->where('order_addresses.type', 'shipping')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select(
                'order_addresses.city',
                DB::raw('count(distinct orders.id) as orders'),
                DB::raw('sum(orders.shipping_total) as shipping'),
            )
            ->groupBy('order_addresses.city')
            ->orderByDesc('shipping')
            ->limit(15)
            ->get();

        $summary = [
            'revenue'   => $current['revenue'],
            'shipments' => $current['shipments'],
            'delivered' => $current['delivered'],
        ];

        return view('admin.reports.shipping', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas(['revenue' => $summary['revenue']], $agg($cFrom, $cTo)) : [],
            'byCourier'   => $byCourier,
            'byCity'      => $byCity,
        ]);
    }

    public function payments(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'captured'    => (float) Payment::whereIn('status', ['captured', 'partially_refunded', 'refunded'])
                ->whereBetween('created_at', [$f, $t])->sum('amount_captured'),
            'refunded'    => (float) Payment::whereIn('status', ['refunded', 'partially_refunded'])
                ->whereBetween('created_at', [$f, $t])->sum('amount_refunded'),
            'failed'      => (float) Payment::whereBetween('created_at', [$f, $t])->where('status', 'failed')->count(),
        ];
        $current = $agg($from, $to);

        $byProvider = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->select(
                'provider',
                DB::raw('count(*) as initiated'),
                DB::raw("sum(case when status = 'captured' then 1 else 0 end) as captured_count"),
                DB::raw('sum(amount_captured) as captured'),
                DB::raw('sum(amount_refunded) as refunded'),
                DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failed"),
            )
            ->groupBy('provider')
            ->orderByDesc('initiated')
            ->get()
            ->map(function ($row) {
                $rate = $row->initiated > 0 ? $row->captured_count / $row->initiated * 100 : 0.0;
                $row->success_rate = round($rate, 1);

                return $row;
            });

        $dailyCaptured = Payment::whereIn('status', ['captured', 'partially_refunded', 'refunded'])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day, sum(amount_captured) as total')
            ->groupBy('day')->orderBy('day')->get();
        $dailyFailed = Payment::where('status', 'failed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')->orderBy('day')->get();

        // Merge into one aligned series for the chart.
        $capturedMap = $dailyCaptured->pluck('total', 'day')->all();
        $failedMap = $dailyFailed->pluck('total', 'day')->all();
        $days = array_values(array_unique(array_merge(
            array_keys($capturedMap),
            array_keys($failedMap),
        )));
        sort($days);
        $unified = [
            'labels'   => array_map(fn ($d) => Carbon::parse($d)->format('M j'), $days),
            'captured' => array_map(fn ($d) => (float) ($capturedMap[$d] ?? 0), $days),
            'failed'   => array_map(fn ($d) => (float) ($failedMap[$d] ?? 0), $days),
        ];

        $summary = [
            'captured' => $current['captured'],
            'refunded' => $current['refunded'],
            'net'      => $current['captured'] - $current['refunded'],
            'failed'   => $current['failed'],
        ];

        return view('admin.reports.payments', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas($summary, $agg($cFrom, $cTo)) : [],
            'byProvider'  => $byProvider,
            'chart'       => $unified,
        ]);
    }

    public function refunds(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'count'  => (float) Refund::whereBetween('created_at', [$f, $t])->count(),
            'amount' => (float) Refund::whereBetween('created_at', [$f, $t])
                ->whereIn('status', ['approved', 'processing', 'completed'])->sum('amount'),
        ];
        $current = $agg($from, $to);

        $byStatus = Refund::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('count(*) as total'), DB::raw('sum(amount) as amount'))
            ->groupBy('status')->orderByDesc('total')->get();

        $byType = Refund::whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('count(*) as total'), DB::raw('sum(amount) as amount'))
            ->groupBy('type')->get();

        $topReasons = Refund::query()
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(fn ($r) => \Illuminate\Support\Str::limit(trim($r->reason), 60) ?: 'Unspecified')
            ->map(fn ($group, $reason) => (object) [
                'reason' => $reason,
                'count'  => $group->count(),
                'amount' => (float) $group->sum('amount'),
            ])
            ->sortByDesc('count')->take(10)->values();

        $recent = Refund::with(['order', 'payment'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')->limit(20)->get();

        $byStatusChart = [
            'labels' => $byStatus->pluck('status')->map(fn ($s) => ucfirst((string) $s))->all(),
            'points' => $byStatus->pluck('total')->map(fn ($v) => (float) $v)->all(),
        ];

        $summary = [
            'count'  => $current['count'],
            'amount' => $current['amount'],
        ];

        return view('admin.reports.refunds', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas($summary, $agg($cFrom, $cTo)) : [],
            'byStatus'    => $byStatus,
            'byType'      => $byType,
            'topReasons'  => $topReasons,
            'recent'      => $recent,
            'chart'       => $byStatusChart,
        ]);
    }

    public function exchanges(Request $request): View
    {
        [$from, $to] = $this->period($request);
        [$cFrom, $cTo] = $this->compareWindow($from, $to);
        $compare = $request->boolean('compare');

        $agg = fn (Carbon $f, Carbon $t) => [
            'total'  => (float) ExchangeRequest::whereBetween('created_at', [$f, $t])->count(),
            'rejected' => (float) ExchangeRequest::whereBetween('created_at', [$f, $t])->where('status', 'rejected')->count(),
            'completed' => (float) ExchangeRequest::whereBetween('created_at', [$f, $t])->where('status', 'completed')->count(),
        ];
        $current = $agg($from, $to);

        $byType = ExchangeRequest::whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')->get();

        $byStatus = ExchangeRequest::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->orderByDesc('total')->get();

        $open = ExchangeRequest::whereIn('status', ['pending', 'reviewing', 'approved'])
            ->with(['order'])
            ->orderBy('created_at')->limit(20)->get();

        $summary = [
            'total'     => $current['total'],
            'completed' => $current['completed'],
            'rejected'  => $current['rejected'],
        ];

        $byStatusChart = [
            'labels' => $byStatus->pluck('status')->map(fn ($s) => ucfirst((string) $s))->all(),
            'points' => $byStatus->pluck('total')->map(fn ($v) => (float) $v)->all(),
        ];

        return view('admin.reports.exchanges', [
            'from' => $from, 'to' => $to,
            'compare'     => $compare,
            'compareFrom' => $cFrom, 'compareTo' => $cTo,
            'summary'     => $summary,
            'deltas'      => $compare ? $this->deltas($summary, $agg($cFrom, $cTo)) : [],
            'byType'      => $byType,
            'byStatus'    => $byStatus,
            'open'        => $open,
            'chart'       => $byStatusChart,
        ]);
    }

    /** Order statuses counted as "selling" (exclude cancelled/returned churn). */
    private function sellingStatuses(): array
    {
        return ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'delivered', 'partially_fulfilled'];
    }

    // -----------------------------------------------------------------------
    // Exports
    // -----------------------------------------------------------------------
    public function export(Request $request, string $type): StreamedResponse|BinaryFileResponse
    {
        [$from, $to] = $this->period($request);

        [$headings, $rows] = $this->rowsFor($type, $from, $to);
        $filename = "{$type}-report-{$from->format('Ymd')}-{$to->format('Ymd')}";

        if ($request->input('format') === 'xlsx') {
            return Excel::download(new ReportExport($headings, $rows, ucfirst($type)), $filename.'.xlsx');
        }

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Headings + rows per report slug (shared by CSV, Excel and the email report).
     */
    private function rowsFor(string $type, Carbon $from, Carbon $to): array
    {
        $n = fn ($value) => (float) $value;

        switch ($type) {
            case 'products':
                $items = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereIn('orders.status', $this->sellingStatuses())
                    ->whereBetween('orders.created_at', [$from, $to])
                    ->select(
                        'order_items.product_name',
                        'order_items.sku',
                        DB::raw('sum(order_items.quantity) as units'),
                        DB::raw('sum(order_items.line_total) as revenue'),
                        DB::raw('sum(order_items.discount_amount) as discount'),
                        DB::raw('sum(order_items.quantity * COALESCE(order_items.unit_cost, 0)) as cost'),
                    )
                    ->groupBy('order_items.product_name', 'order_items.sku')
                    ->orderByDesc('revenue')->get();

                $rows = $items->map(fn ($i) => [
                    $i->product_name, $i->sku, $i->units, $n($i->revenue), $n($i->discount), $n($i->cost),
                ])->all();

                return [['Product', 'SKU', 'Units', 'Revenue', 'Discounts', 'Cost of goods'], $rows];

            case 'coupons':
                $redemptions = CouponRedemption::whereBetween('created_at', [$from, $to])->get()
                    ->groupBy('coupon_id');
                $rows = [];
                foreach ($redemptions as $couponId => $group) {
                    $coupon = Coupon::withTrashed()->find($couponId);
                    $rows[] = [
                        $coupon?->code ?? '#'.$couponId,
                        $coupon?->type ?? '',
                        $group->count(),
                        $n($group->sum('amount')),
                        $n(Order::where('coupon_id', $couponId)
                            ->whereBetween('created_at', [$from, $to])->sum('grand_total')),
                    ];
                }
                usort($rows, fn ($a, $b) => $b[3] <=> $a[3]);

                return [['Coupon', 'Type', 'Redemptions', 'Discount given', 'Revenue influenced'], $rows];

            case 'tax':
                $byProvince = OrderAddress::query()
                    ->join('orders', 'orders.id', '=', 'order_addresses.order_id')
                    ->whereIn('orders.status', $this->sellingStatuses())
                    ->where('order_addresses.type', 'shipping')
                    ->whereBetween('orders.created_at', [$from, $to])
                    ->select(
                        'order_addresses.province',
                        DB::raw('count(distinct orders.id) as orders'),
                        DB::raw('sum(orders.tax_total) as tax'),
                        DB::raw('sum(orders.grand_total) as revenue'),
                    )
                    ->groupBy('order_addresses.province')->orderByDesc('tax')->get();

                $rows = $byProvince->map(fn ($r) => [$r->province, $r->orders, $n($r->tax), $n($r->revenue)])->all();

                return [['Province', 'Orders', 'Tax collected', 'Revenue'], $rows];

            case 'shipping':
                $byCourier = Shipment::whereBetween('created_at', [$from, $to])
                    ->select(
                        'courier',
                        DB::raw('count(*) as shipments'),
                        DB::raw("sum(case when status = 'delivered' then 1 else 0 end) as delivered"),
                        DB::raw("sum(case when status in ('failed', 'returned') then 1 else 0 end) as problem"),
                        DB::raw('avg(shipping_cost) as avg_cost'),
                    )
                    ->groupBy('courier')->orderByDesc('shipments')->get();

                $rows = $byCourier->map(fn ($r) => [
                    $r->courier, $r->shipments, $r->delivered, $r->problem, $n($r->avg_cost),
                ])->all();

                return [['Courier', 'Shipments', 'Delivered', 'Problem', 'Avg. cost'], $rows];

            case 'payments':
                $byProvider = Payment::whereBetween('created_at', [$from, $to])
                    ->select(
                        'provider',
                        'method',
                        DB::raw('count(*) as initiated'),
                        DB::raw("sum(case when status = 'captured' then 1 else 0 end) as captured_count"),
                        DB::raw('sum(amount_captured) as captured'),
                        DB::raw('sum(amount_refunded) as refunded'),
                        DB::raw("sum(case when status = 'failed' then 1 else 0 end) as failed"),
                    )
                    ->groupBy('provider', 'method')->orderByDesc('initiated')->get();

                $rows = $byProvider->map(function ($r) use ($n) {
                    $rate = $r->initiated > 0 ? round($r->captured_count / $r->initiated * 100, 1) : 0.0;

                    return [$r->provider, $r->method, $r->initiated, $r->captured_count, $n($r->captured), $n($r->refunded), $r->failed, $rate];
                })->all();

                return [['Provider', 'Method', 'Initiated', 'Captured', 'Captured amount', 'Refunded', 'Failed', 'Success rate %'], $rows];

            case 'refunds':
                $refunds = Refund::with('order')->whereBetween('created_at', [$from, $to])
                    ->orderBy('created_at')->get();
                $rows = $refunds->map(fn ($r) => [
                    $r->created_at?->toDateTimeString(),
                    $r->order?->number,
                    $n($r->amount),
                    $r->type,
                    $r->status,
                    $r->reason,
                ])->all();

                return [['When', 'Order', 'Amount', 'Type', 'Status', 'Reason'], $rows];

            case 'exchanges':
                $requests = ExchangeRequest::with('order')->whereBetween('created_at', [$from, $to])
                    ->orderBy('created_at')->get();
                $rows = $requests->map(fn ($r) => [
                    $r->created_at?->toDateTimeString(),
                    $r->order?->number,
                    $r->type,
                    $r->status,
                    $r->quantity,
                    \Illuminate\Support\Str::limit(trim($r->reason), 120),
                ])->all();

                return [['When', 'Order', 'Type', 'Status', 'Quantity', 'Reason'], $rows];

            case 'sales':
                $orders = Order::whereBetween('created_at', [$from, $to])->orderBy('created_at')->get();
                $salesRows = $orders->map(fn ($order) => [
                    $order->number,
                    $order->created_at?->toDateTimeString(),
                    is_object($order->status) ? $order->status->value : $order->status,
                    is_object($order->payment_status) ? $order->payment_status->value : $order->payment_status,
                    $n($order->grand_total),
                    $n($order->paid_amount),
                    $n($order->refunded_amount),
                ])->all();

                return [['Order', 'Date', 'Status', 'Payment Status', 'Grand Total', 'Paid', 'Refunded'], $salesRows];

            case 'inventory':
                $variants = ProductVariant::with('product')->get();
                $invRows = $variants->map(fn ($variant) => [
                    $variant->sku,
                    $variant->product?->name,
                    $variant->stock_quantity,
                    $variant->reserved_stock,
                    $variant->stock_quantity - $variant->reserved_stock,
                    $n($variant->cost_price),
                    $variant->low_stock_threshold,
                ])->all();

                return [['SKU', 'Product', 'On Hand', 'Reserved', 'Available', 'Cost', 'Threshold'], $invRows];

            case 'customers':
                $users = User::customers()->withCount('orders')->get();
                $custRows = $users->map(fn ($user) => [
                    $user->name, $user->email, $user->phone, $user->orders_count,
                    $user->created_at?->toDateTimeString(),
                ])->all();

                return [['Name', 'Email', 'Phone', 'Orders', 'Joined'], $custRows];

            case 'staff-activity':
                $logs = AuditLog::whereBetween('created_at', [$from, $to])->orderBy('created_at')->get();
                $logRows = $logs->map(fn ($log) => [
                    $log->created_at?->toDateTimeString(),
                    $log->actor_email,
                    $log->action,
                    $log->module,
                    class_basename((string) $log->target_type).($log->target_id ? '#'.$log->target_id : ''),
                    $log->outcome,
                    $log->ip_address,
                ])->all();

                return [['When', 'Actor', 'Action', 'Module', 'Target', 'Outcome', 'IP'], $logRows];

            default:
                return [['Unsupported report type'], []];
        }
    }

    /**
     * Email the current report (XLSX attached + summary in the email body)
     * to the signed-in staff member.
     */
    public function emailReport(Request $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $user = $request->user();
        [$from, $to] = $this->period($request);
        [$headings, $rows] = $this->rowsFor($type, $from, $to);

        $attachment = Excel::raw(new ReportExport($headings, array_slice($rows, 0, 500), ucfirst($type)), 'Xlsx');

        Mail::to($user->email)->queue(new ReportEmailMail($type, $from, $to, $attachment));
        $this->audit->record('reports.email', null, null, [
            'type' => $type, 'from' => $from->toDateString(), 'to' => $to->toDateString(),
        ]);

        return back()->with('success', "Report queued for {$user->email}.");
=======
    public function export(Request $request, string $type): StreamedResponse
    {
        [$from, $to] = $this->period($request);

        $filename = "{$type}-report-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($type, $from, $to) {
            $out = fopen('php://output', 'w');

            if ($type === 'sales') {
                fputcsv($out, ['Order', 'Date', 'Status', 'Payment Status', 'Grand Total', 'Paid', 'Refunded']);
                Order::whereBetween('created_at', [$from, $to])->orderBy('created_at')
                    ->chunk(200, function ($orders) use ($out) {
                        foreach ($orders as $order) {
                            fputcsv($out, [
                                $order->number,
                                $order->created_at?->toDateTimeString(),
                                is_object($order->status) ? $order->status->value : $order->status,
                                is_object($order->payment_status) ? $order->payment_status->value : $order->payment_status,
                                $order->grand_total,
                                $order->paid_amount,
                                $order->refunded_amount,
                            ]);
                        }
                    });
            } elseif ($type === 'inventory') {
                fputcsv($out, ['SKU', 'Product', 'On Hand', 'Reserved', 'Available', 'Cost', 'Threshold']);
                ProductVariant::with('product')->chunk(200, function ($variants) use ($out) {
                    foreach ($variants as $variant) {
                        fputcsv($out, [
                            $variant->sku,
                            $variant->product?->name,
                            $variant->stock_quantity,
                            $variant->reserved_stock,
                            $variant->stock_quantity - $variant->reserved_stock,
                            $variant->cost_price,
                            $variant->low_stock_threshold,
                        ]);
                    }
                });
            } elseif ($type === 'customers') {
                fputcsv($out, ['Name', 'Email', 'Phone', 'Orders', 'Joined']);
                User::customers()->withCount('orders')->chunk(200, function ($users) use ($out) {
                    foreach ($users as $user) {
                        fputcsv($out, [
                            $user->name,
                            $user->email,
                            $user->phone,
                            $user->orders_count,
                            $user->created_at?->toDateTimeString(),
                        ]);
                    }
                });
            } elseif ($type === 'staff-activity') {
                fputcsv($out, ['When', 'Actor', 'Action', 'Module', 'Target', 'Outcome', 'IP']);
                AuditLog::whereBetween('created_at', [$from, $to])->orderBy('created_at')
                    ->chunk(200, function ($logs) use ($out) {
                        foreach ($logs as $log) {
                            fputcsv($out, [
                                $log->created_at?->toDateTimeString(),
                                $log->actor_email,
                                $log->action,
                                $log->module,
                                class_basename((string) $log->target_type).($log->target_id ? '#'.$log->target_id : ''),
                                $log->outcome,
                                $log->ip_address,
                            ]);
                        }
                    });
            } else {
                fputcsv($out, ['Unsupported report type']);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
>>>>>>> origin/main
    }
}
