@extends('admin.layout')

@section('title', 'Shipping Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Shipping Report</h1>
        <p class="text-sm text-muted mt-1">Shipping revenue, courier performance and destination load for the selected period.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'shipping', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Shipping revenue', 'value' => 'Rs. '.number_format($summary['revenue']), 'key' => 'revenue'],
        ['label' => 'Shipments', 'value' => number_format($summary['shipments']), 'key' => 'shipments'],
        ['label' => 'Delivered', 'value' => number_format($summary['delivered']), 'key' => 'delivered'],
    ], 'deltas' => $deltas])

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">By courier</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Courier</th>
                            <th class="text-right p-3">Shipments</th>
                            <th class="text-right p-3">Delivered</th>
                            <th class="text-right p-3">Problem</th>
                            <th class="text-right p-3">Avg. cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byCourier as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3 font-medium">{{ $row->courier }}</td>
                                <td class="p-3 text-right tabular-nums">{{ number_format($row->shipments) }}</td>
                                <td class="p-3 text-right tabular-nums">{{ number_format($row->delivered) }}</td>
                                <td class="p-3 text-right tabular-nums {{ $row->problem > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($row->problem) }}</td>
                                <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->avg_cost) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-muted text-sm">No shipments in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Top destinations by shipping revenue</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">City</th>
                            <th class="text-right p-3">Orders</th>
                            <th class="text-right p-3">Shipping</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byCity as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3 font-medium">{{ $row->city }}</td>
                                <td class="p-3 text-right tabular-nums">{{ number_format($row->orders) }}</td>
                                <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->shipping) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-8 text-center text-muted text-sm">No shipping in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
