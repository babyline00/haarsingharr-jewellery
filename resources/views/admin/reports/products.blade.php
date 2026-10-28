@extends('admin.layout')

@section('title', 'Products Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Products Report</h1>
        <p class="text-sm text-muted mt-1">Units, revenue, discounts and gross profit per product for the selected period.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'products', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Revenue', 'value' => 'Rs. '.number_format($summary['revenue']), 'key' => 'revenue'],
        ['label' => 'Units sold', 'value' => number_format($summary['units']), 'key' => 'units'],
        ['label' => 'Gross profit', 'value' => 'Rs. '.number_format($summary['profit']), 'key' => 'profit'],
        ['label' => 'Discounts given', 'value' => 'Rs. '.number_format($summary['discount']), 'key' => 'discount'],
    ], 'deltas' => $deltas])

    @unless (empty($chart['labels']))
        <div class="mb-6">
            <x-admin.chart
                :labels="$chart['labels']"
                :series="[['label' => 'Revenue', 'points' => $chart['revenue']]]"
                type="bar" :money="true"
                caption="Daily product revenue"
            />
        </div>
    @endunless

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Top products by revenue</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Product</th>
                        <th class="text-right p-3">Units</th>
                        <th class="text-right p-3">Revenue</th>
                        <th class="text-right p-3">Discounts</th>
                        <th class="text-right p-3">Cost</th>
                        <th class="text-right p-3">Gross profit</th>
                        <th class="text-right p-3">Orders</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($rows as $row)
                        @php $profit = (float) $row->revenue - (float) $row->cost; @endphp
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-medium">{{ $row->product_name }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->units) }}</td>
                            <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->revenue) }}</td>
                            <td class="p-3 text-right tabular-nums text-muted">Rs. {{ number_format($row->discount) }}</td>
                            <td class="p-3 text-right tabular-nums text-muted">Rs. {{ number_format($row->cost) }}</td>
                            <td class="p-3 text-right tabular-nums font-medium {{ $profit >= 0 ? '' : 'text-danger' }}">Rs. {{ number_format($profit) }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->orders) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No product sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
