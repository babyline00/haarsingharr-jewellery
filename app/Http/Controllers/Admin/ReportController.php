<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
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

        $byStatus = (clone $base)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $byProvider = Payment::whereBetween('created_at', [$from, $to])
            ->select('provider', DB::raw('count(*) as total'), DB::raw('sum(amount) as amount'))
            ->groupBy('provider')
            ->get();

        return view('admin.reports.sales', [
            'from' => $from, 'to' => $to,
            'summary' => $summary,
            'daily' => $daily,
            'topProducts' => $topProducts,
            'byStatus' => $byStatus,
            'byProvider' => $byProvider,
        ]);
    }

    public function inventory(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $variants = ProductVariant::with('product');

        $summary = [
            'variants'   => (clone $variants)->count(),
            'units'      => (int) (clone $variants)->sum('stock_quantity'),
            'value'      => (float) (clone $variants)->sum(DB::raw('stock_quantity * cost_price')),
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

        return view('admin.reports.customers', [
            'from' => $from, 'to' => $to,
            'summary' => $summary,
            'daily' => $daily,
            'topCustomers' => $topCustomers,
        ]);
    }

    public function staffActivity(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $logs = AuditLog::whereBetween('created_at', [$from, $to]);

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

        return view('admin.reports.staff-activity', [
            'from' => $from, 'to' => $to,
            'total' => (clone $logs)->count(),
            'byActor' => $byActor,
            'byAction' => $byAction,
            'recent' => $recent,
        ]);
    }

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
    }
}
