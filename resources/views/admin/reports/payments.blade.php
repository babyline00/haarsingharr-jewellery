@extends('admin.layout')

@section('title', 'Payments Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Payments &amp; Reconciliation</h1>
        <p class="text-sm text-muted mt-1">Captured, refunded and failed payments per provider — spot leaks between providers and orders.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'payments', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Captured', 'value' => 'Rs. '.number_format($summary['captured']), 'key' => 'captured'],
        ['label' => 'Refunded', 'value' => 'Rs. '.number_format($summary['refunded']), 'key' => 'refunded'],
        ['label' => 'Net', 'value' => 'Rs. '.number_format($summary['net']), 'key' => 'net'],
        ['label' => 'Failed payments', 'value' => number_format($summary['failed']), 'key' => 'failed'],
    ], 'deltas' => $deltas])

    @unless (empty($chart['labels']))
        <div class="mb-6">
            <x-admin.chart
                :labels="$chart['labels']"
                :series="[
                    ['label' => 'Captured (Rs.)', 'points' => $chart['captured']],
                    ['label' => 'Failed (count)', 'points' => $chart['failed']],
                ]"
                type="line"
                caption="Captured amounts vs failed payments per day"
            />
        </div>
    @endunless

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">By provider &amp; method</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Provider</th>
                        <th class="text-left p-3">Method</th>
                        <th class="text-right p-3">Initiated</th>
                        <th class="text-right p-3">Captured</th>
                        <th class="text-right p-3">Captured (Rs.)</th>
                        <th class="text-right p-3">Refunded (Rs.)</th>
                        <th class="text-right p-3">Failed</th>
                        <th class="text-right p-3">Success rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($byProvider as $row)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-medium">{{ ucfirst((string) $row->provider) }}</td>
                            <td class="p-3 text-muted">{{ $row->method ? ucfirst((string) $row->method) : '—' }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->initiated) }}</td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($row->captured_count) }}</td>
                            <td class="p-3 text-right tabular-nums font-medium">Rs. {{ number_format($row->captured) }}</td>
                            <td class="p-3 text-right tabular-nums text-muted">Rs. {{ number_format($row->refunded) }}</td>
                            <td class="p-3 text-right tabular-nums {{ $row->failed > 0 ? 'text-danger' : '' }}">{{ number_format($row->failed) }}</td>
                            <td class="p-3 text-right tabular-nums">{{ $row->success_rate }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-8 text-center text-muted text-sm">No payments in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
