@extends('admin.layout')

@section('title', 'Exchanges Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Exchanges &amp; Returns Report</h1>
        <p class="text-sm text-muted mt-1">Exchange, return and refund-request funnel for the selected period.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'exchanges', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Requests', 'value' => number_format($summary['total']), 'key' => 'total'],
        ['label' => 'Completed', 'value' => number_format($summary['completed']), 'key' => 'completed'],
        ['label' => 'Rejected', 'value' => number_format($summary['rejected']), 'key' => 'rejected'],
    ], 'deltas' => $deltas])

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        <div class="card p-4">
            <h2 class="font-serif text-base mb-3">By type</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($byType as $row)
                        <tr>
                            <td class="p-2">{{ ucfirst((string) $row->type) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ number_format($row->total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="p-4 text-center text-muted text-sm">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-admin.chart
            :labels="$chart['labels'] ?? []"
            :series="[['label' => 'Requests', 'points' => $chart['points'] ?? []]]"
            type="bar" :money="false"
            caption="Requests by status"
        />
    </div>

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Open work queue (oldest first)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Number</th>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Quantity</th>
                        <th class="text-left p-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($open as $request)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-medium">
                                <a href="{{ route('admin.exchanges.show', $request) }}" class="link">{{ $request->number }}</a>
                            </td>
                            <td class="p-3">{{ $request->order?->number }}</td>
                            <td class="p-3 text-muted">{{ ucfirst((string) $request->type) }}</td>
                            <td class="p-3"><x-admin.status-pill :status="$request->status" /></td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($request->quantity) }}</td>
                            <td class="p-3 text-muted text-xs">{{ \Illuminate\Support\Str::limit(trim($request->reason), 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No open requests in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
