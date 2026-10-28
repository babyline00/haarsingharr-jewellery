@extends('admin.layout')

@section('title', 'Tax Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Tax Report</h1>
        <p class="text-sm text-muted mt-1">Tax collected on sold orders, broken down by province and day.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'tax', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Tax collected', 'value' => 'Rs. '.number_format($summary['tax']), 'key' => 'tax'],
        ['label' => 'Orders', 'value' => number_format($summary['orders']), 'key' => 'orders'],
        ['label' => 'Avg. tax / order', 'value' => 'Rs. '.number_format($summary['avg_tax']), 'key' => 'avg_tax'],
    ], 'deltas' => $deltas])

    @unless (empty($chart['labels']))
        <div class="mb-6">
            <x-admin.chart
                :labels="$chart['labels']"
                :series="[['label' => 'Tax per day', 'points' => $chart['points']]]"
                type="line" :money="true"
                caption="Tax collected per day"
            />
        </div>
    @endunless

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Tax by province</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Province</th>
                        <th class="text-right p-3">Orders</th>
                        <th class="text-right p-3">Revenue</th>
                        <th class="text-right p-3">Tax collected</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($byProvince as $row)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-medium">{{ $row->province }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->orders) }}</td>
                            <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->revenue) }}</td>
                            <td class="p-3 text-right tabular-nums font-medium">Rs. {{ number_format($row->tax) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-muted text-sm">No taxable orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
