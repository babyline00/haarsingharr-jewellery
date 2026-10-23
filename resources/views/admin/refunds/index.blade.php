@extends('admin.layout')

@section('title', 'Refunds — Haarsingharr Admin')

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
            <h1 class="font-serif text-2xl md:text-3xl">Refunds</h1>
            <p class="text-sm text-muted mt-1">Review and approve customer refunds.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (array_keys($colors) as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                        <th class="text-left p-3">Payment</th>
                        <th class="text-right p-3">Amount</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Requested</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($refunds as $refund)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($refund->order)
                                    <a href="{{ route('admin.orders.show', $refund->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $refund->order->number }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $refund->payment ? ucfirst($refund->payment->provider) : '—' }}</td>
                            <td class="p-3 text-right font-medium">{{ number_format($refund->amount, 2) }}</td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst($refund->type) }}</td>
                            <td class="p-3"><span class="badge {{ $colors[$refund->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($refund->status) }}</span></td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $refund->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                                @if ($refund->canBeApproved())
                                    @can('refunds.approve')
                                    <form action="{{ route('admin.refunds.approve', $refund) }}" method="post" class="inline" onsubmit="return confirm('Approve and process this refund?')">
                                        @csrf
                                        <button class="text-xs text-emerald-700 hover:underline ml-2">Approve</button>
                                    </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($refunds->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No refunds found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $refunds->links() }}</div>
    </div>
@endsection
