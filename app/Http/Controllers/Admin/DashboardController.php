<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Aggregates are cached and keyed on the highest order id — any new
        // order (or a cancelled/updated one via its id bump? no: cancellation
        // only flips status) busts the key on next seed/order creation. Status
        // changes without a new order fall to the 10-minute TTL.
        $aggregates = \Illuminate\Support\Facades\Cache::remember(
            'dashboard:aggregates:'.Order::max('id'),
            now()->addMinutes(10),
            function () {
                return [
                    'today'          => Order::todays()->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
                    'yesterday'      => Order::whereDate('created_at', now()->subDay()->toDateString())->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
                    'week'           => Order::where('created_at', '>=', now()->subDays(7))->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
                    'month'          => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->whereNotIn('status', ['cancelled', 'returned'])->sum('grand_total'),
                    'pendingOrders'  => Order::status(OrderStatus::Pending)->count(),
                    'unpaidOrders'   => Order::whereIn('payment_status', [PaymentStatus::Unpaid, PaymentStatus::Pending])->count(),
                    'newCustomers'   => User::customers()->where('created_at', '>=', now()->subDays(7))->count(),
                    'avgOrderValue'  => Order::where('created_at', '>=', now()->subDays(30))->whereNotIn('status', ['cancelled', 'returned'])->avg('grand_total') ?? 0,
                    'topProducts'    => OrderItem::select('product_id', 'product_name',
                            DB::raw('SUM(quantity) as units'), DB::raw('SUM(line_total) as revenue'))
                        ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
                        ->where('created_at', '>=', now()->subDays(30))
                        ->groupBy('product_id', 'product_name')
                        ->orderByDesc('revenue')
                        ->limit(5)
                        ->get(),
                ];
            },
        );

        $today = $aggregates['today'];
        $week = $aggregates['week'];
        $month = $aggregates['month'];
        $pendingOrders = $aggregates['pendingOrders'];
        $unpaidOrders = $aggregates['unpaidOrders'];
        $newCustomers = $aggregates['newCustomers'];
        $avgOrderValue = $aggregates['avgOrderValue'];
        $topProducts = $aggregates['topProducts'];

        // Real day-over-day revenue delta (null when yesterday had no sales —
        // a percentage against zero is meaningless).
        $yesterday = (float) $aggregates['yesterday'];
        $revenueDelta = $yesterday > 0
            ? (int) round((($today - $yesterday) / $yesterday) * 100)
            : null;

        $recentOrders = Order::latest()->limit(8)->with('user', 'items')->get();

        $lowStockAlerts = InventoryAlert::where('is_resolved', false)
            ->where('type', 'low_stock')
            ->with('productVariant.product')
            ->latest()
            ->limit(6)
            ->get();

        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
            ->whereMonth('created_at', now()->month)
            ->groupBy('status')
            ->pluck('count', 'status');

        // Daily sales trend (last 14 days)
        $salesTrend = Order::selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard.index', compact(
            'today', 'yesterday', 'revenueDelta', 'week', 'month', 'pendingOrders', 'unpaidOrders',
            'newCustomers', 'avgOrderValue', 'recentOrders', 'topProducts',
            'lowStockAlerts', 'ordersByStatus', 'salesTrend'
        ));
    }
}
