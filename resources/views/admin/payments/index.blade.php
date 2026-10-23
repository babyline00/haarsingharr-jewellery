@extends('admin.layout')

@section('title', 'Payments — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Payments</h1>
            <p class="text-sm text-muted mt-1">Gateway transactions and their current state.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search transaction or payment id" class="flex-1 min-w-[200px]">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['pending', 'initiated', 'authorized', 'captured', 'failed', 'cancelled', 'refunded', 'partially_refunded'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Provider</th>
                        <th class="text-left p-3">Reference</th>
                        <th class="text-right p-3">Amount</th>
                        <th class="text-right p-3">Captured</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($payments as $payment)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $payment->order->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst($payment->provider) }} <span class="text-xs text-muted">/ {{ $payment->method }}</span></td>
                            <td class="p-3 text-xs font-mono text-muted">{{ $payment->provider_transaction_ref ?: $payment->provider_payment_id ?: '—' }}</td>
                            <td class="p-3 text-right font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="p-3 text-right text-charcoal-soft">{{ number_format($payment->amount_captured, 2) }}</td>
                            <td class="p-3">
                                @php
                                    $colors = [
                                        'pending' => 'bg-slate-100 text-slate-700',
                                        'initiated' => 'bg-blue-100 text-blue-800',
                                        'authorized' => 'bg-indigo-100 text-indigo-800',
                                        'captured' => 'bg-emerald-100 text-emerald-800',
                                        'failed' => 'bg-rose-100 text-rose-800',
                                        'cancelled' => 'bg-slate-100 text-slate-700',
                                        'refunded' => 'bg-blue-100 text-blue-800',
                                        'partially_refunded' => 'bg-orange-100 text-orange-800',
                                    ];
                                @endphp
                                <span class="badge {{ $colors[$payment->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucwords(str_replace('_', ' ', $payment->status)) }}</span>
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $payment->created_at?->format('M j, Y H:i') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    @if ($payments->isEmpty())
                        <tr><td colspan="8" class="p-8 text-center text-muted text-sm">No payments found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $payments->links() }}</div>
    </div>
@endsection
