@extends('admin.layout')

@section('title', 'Coupons Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Coupons Report</h1>
        <p class="text-sm text-muted mt-1">Redemptions, discount spend and influenced revenue per coupon for the selected period.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'coupons', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Redemptions', 'value' => number_format($summary['redemptions']), 'key' => 'redemptions'],
        ['label' => 'Discount given', 'value' => 'Rs. '.number_format($summary['given']), 'key' => 'given'],
        ['label' => 'Revenue with coupon', 'value' => 'Rs. '.number_format($summary['coupon_revenue']), 'key' => 'coupon_revenue'],
        ['label' => 'Avg. coupon order', 'value' => 'Rs. '.number_format($summary['avg_order']), 'key' => 'avg_order'],
    ], 'deltas' => $deltas])

    @unless (empty($chart['labels']))
        <div class="mb-6">
            <x-admin.chart
                :labels="$chart['labels']"
                :series="[['label' => 'Discounts per day', 'points' => $chart['points']]]"
                type="bar" :money="true"
                caption="Discounts given per day"
            />
        </div>
    @endunless

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Coupon performance</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Coupon</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Redemptions</th>
                        <th class="text-right p-3">Discount given</th>
                        <th class="text-right p-3">Revenue influenced</th>
                        <th class="text-right p-3">Avg. order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs">{{ $row->code }}</td>
                            <td class="p-3 text-muted">{{ ucfirst((string) $row->type) }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->redemptions) }}</td>
                            <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->given) }}</td>
                            <td class="p-3 text-right tabular-nums font-medium">Rs. {{ number_format($row->revenue) }}</td>
                            <td class="p-3 text-right tabular-nums">Rs. {{ number_format($row->avg_order) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No coupon redemptions in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
