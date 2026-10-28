@extends('admin.layout')

@section('title', 'Dashboard — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Welcome, {{ auth()->user()->first_name }}</h1>
            <p class="text-sm text-muted mt-1">Haarsingharr back office — {{ now()->format('l, j F Y') }}</p>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Revenue (Today)</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($today) }}</div>
            @if ($yesterday > 0)
                <div class="text-xs mt-1 {{ $revenueDelta >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ $revenueDelta >= 0 ? '↑' : '↓' }} {{ abs($revenueDelta) }}% vs yesterday
                </div>
            @elseif ($today > 0)
                <div class="text-xs text-success mt-1">First orders today</div>
            @else
                <div class="text-xs text-muted mt-1">No sales yet today</div>
            @endif
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Revenue (7 days)</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($week) }}</div>
            <div class="text-xs text-muted mt-1">{{ $pendingOrders }} pending orders</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Revenue (Month)</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($month) }}</div>
            <div class="text-xs text-muted mt-1">AOV: Rs. {{ number_format($avgOrderValue) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">New Customers</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">{{ $newCustomers }}</div>
            <div class="text-xs text-muted mt-1">{{ $unpaidOrders }} unpaid orders</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Recent orders --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Recent Orders</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-champagne-dark hover:underline">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Order</th>
                            <th class="text-left p-3">Customer</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-right p-3">Total</th>
                            <th class="text-right p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-champagne-dark hover:underline">{{ $order->number }}</a></td>
                                <td class="p-3 text-charcoal-soft">{{ $order->user?->name ?? $order->guest_email }}</td>
                                <td class="p-3"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                                <td class="p-3 text-right font-medium">Rs. {{ number_format($order->grand_total) }}</td>
                                <td class="p-3 text-right text-xs text-muted">{{ $order->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sales trend --}}
        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Sales Trend (14 days)</h2>
            <div class="space-y-2">
                @php $max = $salesTrend->max('total') ?: 1; @endphp
                @foreach ($salesTrend as $day)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-muted">{{ \Carbon\Carbon::parse($day->date)->format('M j') }}</span>
                            <span class="font-medium">Rs. {{ number_format($day->total) }}</span>
                        </div>
                        <div class="h-2 bg-ivory-alt rounded-full overflow-hidden">
                            <div class="h-full bg-champagne rounded-full" style="width: {{ ($day->total / $max) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top products --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Top Products (30 days)</h2>
                <a href="{{ route('admin.reports.sales') }}" class="text-xs text-champagne-dark hover:underline">Full report →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Product</th>
                            <th class="text-right p-3">Units Sold</th>
                            <th class="text-right p-3">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @foreach ($topProducts as $row)
                            <tr class="hover:bg-ivory-alt/30">
                                <td class="p-3">
                                    @if ($row->product_id)
                                        <a href="{{ route('admin.products.show', $row->product_id) }}" class="hover:text-champagne-dark">{{ $row->product_name }}</a>
                                    @else
                                        {{ $row->product_name }}
                                    @endif
                                </td>
                                <td class="p-3 text-right">{{ $row->units }}</td>
                                <td class="p-3 text-right font-medium">Rs. {{ number_format($row->revenue) }}</td>
                            </tr>
                        @endforeach
                        @if ($topProducts->isEmpty())
                            <tr><td colspan="3" class="p-6 text-center text-muted text-sm">No sales in the past 30 days.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low stock alerts --}}
        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Low Stock Alerts</h2>
            <div class="space-y-3">
                @foreach ($lowStockAlerts as $alert)
                    <div class="flex items-center gap-3 p-3 bg-warning/5 border border-warning/20 rounded-md">
                        <div class="w-8 h-8 rounded-full bg-warning/10 flex items-center justify-center text-warning">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ $alert->productVariant?->product?->name ?? 'Unknown' }}</div>
                            <div class="text-xs text-muted">SKU: {{ $alert->productVariant?->sku ?? '-' }} — only {{ $alert->current_stock }} left</div>
                        </div>
                        <a href="{{ route('admin.inventory.index', ['variant' => $alert->product_variant_id]) }}" class="text-xs text-champagne-dark hover:underline">Adjust</a>
                    </div>
                @endforeach
                @if ($lowStockAlerts->isEmpty())
                    <div class="text-center text-sm text-muted py-6">All inventory healthy. 🌟</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Database & System Info --}}
    <div class="grid md:grid-cols-2 gap-6 mt-6">
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-serif text-lg">Database Status</h2>
                <a href="{{ route('admin.database.info') }}" class="text-xs text-champagne-dark hover:underline">View details →</a>
            </div>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Connection</span>
                    <span class="font-medium text-success flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                        {{ config('database.default') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Host</span>
                    <span class="font-medium">{{ config('database.connections.' . config('database.default') . '.host') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Database</span>
                    <span class="font-medium">{{ config('database.connections.' . config('database.default') . '.database') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Products</span>
                    <span class="font-medium">{{ \App\Models\Product::count() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Orders</span>
                    <span class="font-medium">{{ \App\Models\Order::count() }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted">Customers</span>
                    <span class="font-medium">{{ \App\Models\User::customers()->count() }}</span>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-serif text-lg">Quick Actions</h2>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('admin.products.create') }}" class="btn-outline text-sm text-center">New Product</a>
                <a href="{{ route('admin.orders.index') }}" class="btn-outline text-sm text-center">View Orders</a>
                <a href="{{ route('admin.customers.index') }}" class="btn-outline text-sm text-center">Customers</a>
                <a href="{{ route('admin.reports.sales') }}" class="btn-outline text-sm text-center">Reports</a>
                <a href="{{ route('admin.settings.index') }}" class="btn-outline text-sm text-center">Settings</a>
                <a href="{{ route('admin.database.info') }}" class="btn-outline text-sm text-center">Database</a>
            </div>
        </div>
    </div>

    {{-- Orders by status breakdown --}}
    <div class="card p-5 mt-6">
        <h2 class="font-serif text-lg mb-4">Orders by Status — {{ now()->format('F Y') }}</h2>
        <div class="grid grid-cols-3 md:grid-cols-5 gap-4 text-center">
            @php $total = $ordersByStatus->sum() ?: 1; @endphp
            @foreach ($ordersByStatus as $status => $count)
                <div class="p-4 bg-ivory-alt/30 rounded-lg">
                    <div class="text-2xl font-serif">{{ $count }}</div>
                    <div class="text-xs text-muted mt-1 capitalize">{{ str_replace('_', ' ', $status) }}</div>
                    <div class="text-xs text-charcoal-soft">{{ round(($count / $total) * 100) }}%</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
