@extends('admin.layout')

@section('title', 'Request ' . $exchange->number . ' — Haarsingharr Admin')

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
            <h1 class="font-serif text-2xl md:text-3xl">{{ $exchange->number }}</h1>
            <p class="text-sm text-muted mt-1">{{ ucfirst($exchange->type) }} request · {{ $exchange->created_at?->format('M j, Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $statusColors[$exchange->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($exchange->status) }}</span>
            <a href="{{ route('admin.exchanges.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Request Details</h2>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Order</dt>
                        <dd class="mt-1">
                            @if ($exchange->order)
                                <a href="{{ route('admin.orders.show', $exchange->order) }}" class="text-champagne-dark hover:underline font-medium">{{ $exchange->order->number }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Item</dt><dd class="mt-1">{{ $exchange->orderItem?->product_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Quantity</dt><dd class="mt-1">{{ $exchange->quantity }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Requested variant</dt><dd class="mt-1">{{ $exchange->requestedVariant?->sku ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Refund amount</dt><dd class="mt-1">{{ $exchange->refund_amount ? number_format($exchange->refund_amount, 2) : '—' }}</dd></div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Linked refund</dt>
                        <dd class="mt-1">
                            @if ($exchange->refund)
                                <a href="{{ route('admin.refunds.show', $exchange->refund) }}" class="text-champagne-dark hover:underline">Refund #{{ $exchange->refund->id }}</a>
                            @else — @endif
                        </dd>
                    </div>
                </dl>

                <h3 class="font-serif text-base mt-5 mb-1">Customer reason</h3>
                <p class="text-sm whitespace-pre-line">{{ $exchange->reason }}</p>
                @if ($exchange->customer_notes)
                    <h3 class="font-serif text-base mt-4 mb-1">Customer notes</h3>
                    <p class="text-sm text-charcoal-soft whitespace-pre-line">{{ $exchange->customer_notes }}</p>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="p-5 pb-3"><h2 class="font-serif text-lg">Order Items</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                            <tr>
                                <th class="text-left p-3">Product</th>
                                <th class="text-right p-3">Qty</th>
                                <th class="text-right p-3">Line total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-charcoal/5">
                            @foreach ($exchange->order?->items ?? [] as $item)
                                <tr class="{{ $exchange->order_item_id === $item->id ? 'bg-champagne/5' : '' }}">
                                    <td class="p-3">{{ $item->product_name }} @if ($item->variant_title) <span class="text-xs text-muted">{{ $item->variant_title }}</span> @endif</td>
                                    <td class="p-3 text-right">{{ $item->quantity }}</td>
                                    <td class="p-3 text-right">{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Review</h2>
                @can('exchanges.update')
                <form action="{{ route('admin.exchanges.update', $exchange) }}" method="post" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach (array_keys($statusColors) as $status)
                                <option value="{{ $status }}" @selected(old('status', $exchange->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="refund_amount">Refund amount</label>
                        <input type="number" step="0.01" min="0" id="refund_amount" name="refund_amount" value="{{ old('refund_amount', $exchange->refund_amount) }}">
                    </div>
                    <div>
                        <label for="admin_notes">Admin notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4">{{ old('admin_notes', $exchange->admin_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn-gold w-full">Save</button>
                </form>
                @else
                    <p class="text-sm text-muted">{{ $exchange->admin_notes ?: 'No notes.' }}</p>
                @endcan
            </div>
        </aside>
    </div>
@endsection
