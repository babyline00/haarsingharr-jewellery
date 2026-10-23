@extends('admin.layout')

@section('title', 'Returns & Exchanges — Haarsingharr Admin')

@php
    $statusColors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'reviewing' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-indigo-100 text-indigo-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Returns &amp; Exchanges</h1>
            <p class="text-sm text-muted mt-1">Review customer return, exchange and refund requests.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search request or order number" class="flex-1 min-w-[200px]">
        <select name="type">
            <option value="">All types</option>
            @foreach (['return', 'exchange', 'refund'] as $type)
                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (array_keys($statusColors) as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Request</th>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Item</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Qty</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($exchanges as $exchange)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs">{{ $exchange->number }}</td>
                            <td class="p-3">
                                @if ($exchange->order)
                                    <a href="{{ route('admin.orders.show', $exchange->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $exchange->order->number }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft max-w-xs">{{ $exchange->orderItem?->product_name ?? '—' }}</td>
                            <td class="p-3">{{ ucfirst($exchange->type) }}</td>
                            <td class="p-3"><span class="badge {{ $statusColors[$exchange->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($exchange->status) }}</span></td>
                            <td class="p-3 text-right">{{ $exchange->quantity }}</td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $exchange->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right"><a href="{{ route('admin.exchanges.show', $exchange) }}" class="text-xs text-champagne-dark hover:underline">Review</a></td>
                        </tr>
                    @endforeach
                    @if ($exchanges->isEmpty())
                        <tr><td colspan="8" class="p-8 text-center text-muted text-sm">No requests found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $exchanges->links() }}</div>
    </div>
@endsection
