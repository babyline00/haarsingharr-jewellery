@extends('admin.layout')

@section('title', 'Refunds Report — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Refunds Report</h1>
        <p class="text-sm text-muted mt-1">Refund volume, funnel and reasons for the selected period.</p>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'refunds', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Refunds', 'value' => number_format($summary['count']), 'key' => 'count'],
        ['label' => 'Amount (approved+)', 'value' => 'Rs. '.number_format($summary['amount']), 'key' => 'amount'],
    ], 'deltas' => $deltas])

    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="card p-4">
            <h2 class="font-serif text-base mb-3">By status</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($byStatus as $row)
                        <tr>
                            <td class="p-2">{{ ucfirst((string) $row->status) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ number_format($row->total) }}</td>
                            <td class="p-2 text-right tabular-nums text-muted">Rs. {{ number_format($row->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-4 text-center text-muted text-sm">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card p-4">
            <h2 class="font-serif text-base mb-3">Full vs partial</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($byType as $row)
                        <tr>
                            <td class="p-2">{{ ucfirst((string) $row->type) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ number_format($row->total) }}</td>
                            <td class="p-2 text-right tabular-nums text-muted">Rs. {{ number_format($row->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-4 text-center text-muted text-sm">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card p-4">
            <h2 class="font-serif text-base mb-3">Top reasons</h2>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($topReasons as $row)
                        <tr>
                            <td class="p-2">{{ $row->reason }}</td>
                            <td class="p-2 text-right tabular-nums">{{ number_format($row->count) }}</td>
                            <td class="p-2 text-right tabular-nums text-muted whitespace-nowrap">Rs. {{ number_format($row->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-4 text-center text-muted text-sm">None.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @unless (empty($chart['labels']))
        <div class="mb-6">
            <x-admin.chart
                :labels="$chart['labels']"
                :series="[['label' => 'Refunds', 'points' => $chart['points']]]"
                type="bar" :money="true"
                caption="Refunds by status"
            />
        </div>
    @endunless

    <div class="card overflow-hidden">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Latest refunds</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">When</th>
                        <th class="text-left p-3">Order</th>
                        <th class="text-right p-3">Amount</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($recent as $refund)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 whitespace-nowrap">{{ $refund->created_at?->format('M j, Y H:i') }}</td>
                            <td class="p-3">
                                @if ($refund->order)
                                    <a href="{{ route('admin.orders.show', $refund->order) }}" class="link">{{ $refund->order->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 text-right tabular-nums">Rs. {{ number_format($refund->amount) }}</td>
                            <td class="p-3 text-muted">{{ ucfirst((string) $refund->type) }}</td>
                            <td class="p-3"><x-admin.status-pill :status="$refund->status" /></td>
                            <td class="p-3 text-muted text-xs">{{ \Illuminate\Support\Str::limit(trim($refund->reason), 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No refunds in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
