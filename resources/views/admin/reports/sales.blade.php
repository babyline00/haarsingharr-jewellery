@extends('admin.layout')

@section('title', 'Sales Report — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Sales Report</h1>
            <p class="text-sm text-muted mt-1">Revenue, orders and product performance for the selected period.</p>
        </div>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'sales'])

    {{-- Summary --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Orders</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">{{ number_format($summary['orders']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Revenue</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($summary['revenue']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Paid</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($summary['paid']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Refunded</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($summary['refunded']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Avg. Order Value</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($summary['average']) }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Daily --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Daily Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Date</th>
                            <th class="text-right p-3">Orders</th>
                            <th class="text-right p-3">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($daily as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3">{{ \Illuminate\Support\Carbon::parse($row->day)->format('D, M j, Y') }}</td>
                                <td class="p-3 text-right">{{ number_format($row->orders) }}</td>
                                <td class="p-3 text-right font-medium">Rs. {{ number_format($row->revenue) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-8 text-center text-muted text-sm">No orders in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Status + provider --}}
        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="p-5 border-b border-charcoal/5">
                    <h2 class="font-serif text-lg">By Order Status</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byStatus as $row)
                            <tr>
                                <td class="p-3">
                                    @php $enum = \App\Enums\OrderStatus::tryFrom($row->status instanceof \App\Enums\OrderStatus ? $row->status->value : $row->status); @endphp
                                    <span class="badge {{ $enum?->color() ?? 'bg-ivory-alt text-charcoal' }}">{{ $enum?->label() ?? $row->status }}</span>
                                </td>
                                <td class="p-3 text-right font-medium">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-5 text-center text-muted text-sm">No data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card overflow-hidden">
                <div class="p-5 border-b border-charcoal/5">
                    <h2 class="font-serif text-lg">Payments by Provider</h2>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byProvider as $row)
                            <tr>
                                <td class="p-3 capitalize">{{ str_replace('_', ' ', $row->provider) }}</td>
                                <td class="p-3 text-right">{{ number_format($row->total) }}</td>
                                <td class="p-3 text-right font-medium">Rs. {{ number_format($row->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td class="p-5 text-center text-muted text-sm">No payments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top products --}}
    <div class="card overflow-hidden mt-6">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Top Products</h2>
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
                    @forelse ($topProducts as $row)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">{{ $row->product_name }}</td>
                            <td class="p-3 text-right">{{ number_format($row->units) }}</td>
                            <td class="p-3 text-right font-medium">Rs. {{ number_format($row->revenue) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-8 text-center text-muted text-sm">No product sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
