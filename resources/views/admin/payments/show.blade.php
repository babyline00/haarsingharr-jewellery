@extends('admin.layout')

@section('title', 'Payment ' . ($payment->provider_transaction_ref ?: $payment->id) . ' — Haarsingharr Admin')

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

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Payment #{{ $payment->id }}</h1>
            <p class="text-sm text-muted mt-1">
                {{ ucfirst($payment->provider) }} · {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $colors[$payment->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucwords(str_replace('_', ' ', $payment->status)) }}</span>
            <a href="{{ route('admin.payments.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Details</h2>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Order</dt>
                        <dd class="mt-1">
                            @if ($payment->order)
                                <a href="{{ route('admin.orders.show', $payment->order) }}" class="text-champagne-dark hover:underline font-medium">{{ $payment->order->number }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Method</dt><dd class="mt-1">{{ $payment->method ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Transaction ref</dt><dd class="mt-1 font-mono text-xs">{{ $payment->provider_transaction_ref ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider payment id</dt><dd class="mt-1 font-mono text-xs">{{ $payment->provider_payment_id ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider status</dt><dd class="mt-1">{{ $payment->provider_status ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Merchant id</dt><dd class="mt-1 font-mono text-xs">{{ $payment->provider_merchant_id ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Captured</dt><dd class="mt-1">{{ $payment->currency }} {{ number_format($payment->amount_captured, 2) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Refunded</dt><dd class="mt-1">{{ $payment->currency }} {{ number_format($payment->amount_refunded, 2) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Initiated</dt><dd class="mt-1 text-muted">{{ $payment->initiated_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Captured at</dt><dd class="mt-1 text-muted">{{ $payment->captured_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Failed at</dt><dd class="mt-1 text-muted">{{ $payment->failed_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Event Timeline</h2>
                @if ($payment->events->isNotEmpty())
                    <ol class="relative border-l border-charcoal/10 ml-2 space-y-4">
                        @foreach ($payment->events as $event)
                            <li class="ml-4">
                                <div class="flex items-center gap-2">
                                    <span class="badge bg-ivory-alt text-charcoal-soft">{{ ucwords(str_replace('_', ' ', $event->type)) }}</span>
                                    <span class="text-xs text-muted">{{ $event->created_at?->format('M j, Y H:i') }}</span>
                                    @if ($event->is_verified) <span class="text-xs text-emerald-700">verified</span> @endif
                                </div>
                                @if ($event->note) <p class="text-xs text-muted mt-1">{{ $event->note }}</p> @endif
                                @if ($event->amount) <p class="text-xs mt-1">{{ $event->currency }} {{ number_format($event->amount, 2) }}</p> @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-muted">No events recorded.</p>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Refunds</h2>
                @php $refunds = $payment->refunds ?? collect(); @endphp
                @if ($refunds->isNotEmpty())
                    <div class="divide-y divide-charcoal/5 text-sm">
                        @foreach ($refunds as $refund)
                            <a href="{{ route('admin.refunds.show', $refund) }}" class="py-2 flex items-center justify-between hover:text-champagne-dark">
                                <span>{{ $payment->currency }} {{ number_format($refund->amount, 2) }}</span>
                                <span class="badge bg-slate-100 text-slate-700">{{ ucfirst($refund->status) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-muted">No refunds for this payment.</p>
                @endif
            </div>

            @if ($payment->provider_payload)
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Provider Payload</h2>
                    <pre class="text-xs bg-ivory-alt/40 p-3 rounded overflow-x-auto">{{ json_encode($payment->provider_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif

            @if ($payment->provider_response)
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Provider Response</h2>
                    <pre class="text-xs bg-ivory-alt/40 p-3 rounded overflow-x-auto">{{ json_encode($payment->provider_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </aside>
    </div>
@endsection
