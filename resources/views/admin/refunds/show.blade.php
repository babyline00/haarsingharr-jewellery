@extends('admin.layout')

@section('title', 'Refund #' . $refund->id . ' — Haarsingharr Admin')

@php
    $colors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-rose-100 text-rose-800',
        'cancelled' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Refund #{{ $refund->id }}</h1>
            <p class="text-sm text-muted mt-1">{{ ucfirst($refund->type) }} · {{ number_format($refund->amount, 2) }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $colors[$refund->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($refund->status) }}</span>
            <a href="{{ route('admin.refunds.index') }}" class="btn-ghost text-sm">Back</a>
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
                            @if ($refund->order)
                                <a href="{{ route('admin.orders.show', $refund->order) }}" class="text-champagne-dark hover:underline font-medium">{{ $refund->order->number }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Payment</dt>
                        <dd class="mt-1">
                            @if ($refund->payment)
                                <a href="{{ route('admin.payments.show', $refund->payment) }}" class="text-champagne-dark hover:underline font-medium">#{{ $refund->payment->id }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Amount</dt><dd class="mt-1 font-medium">{{ number_format($refund->amount, 2) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider ref</dt><dd class="mt-1 font-mono text-xs">{{ $refund->provider_ref ?: '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Approved by</dt><dd class="mt-1">{{ $refund->approver?->email ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Approved at</dt><dd class="mt-1 text-muted">{{ $refund->approved_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Processed at</dt><dd class="mt-1 text-muted">{{ $refund->processed_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Created</dt><dd class="mt-1 text-muted">{{ $refund->created_at?->format('M j, Y H:i') }}</dd></div>
                </dl>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Reason</h2>
                <p class="text-sm whitespace-pre-line">{{ $refund->reason }}</p>
                @if ($refund->notes)
                    <h3 class="font-serif text-base mt-4 mb-1">Notes</h3>
                    <p class="text-sm text-charcoal-soft whitespace-pre-line">{{ $refund->notes }}</p>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            @if ($refund->canBeApproved())
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-2">Approval</h2>
                    <p class="text-sm text-muted mb-4">Approving will trigger the gateway refund where supported.</p>
                    @can('refunds.approve')
                    <form action="{{ route('admin.refunds.approve', $refund) }}" method="post" onsubmit="return confirm('Approve and process this refund?')">
                        @csrf
                        <button type="submit" class="btn-gold w-full">Approve &amp; Process</button>
                    </form>
                    @else
                    <p class="text-sm text-muted">You do not have permission to approve refunds.</p>
                    @endcan
                </div>
            @else
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-2">Status</h2>
                    <p class="text-sm text-muted">This refund is <strong>{{ $refund->status }}</strong> and cannot be approved again.</p>
                </div>
            @endif
        </aside>
    </div>
@endsection
