@extends('admin.layout')

@section('title', 'Orders — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Orders</h1>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Order #, email, phone" class="flex-1 min-w-[200px]">
        <select name="status">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
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
                        <th class="text-left p-3">Customer</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Payment</th>
                        <th class="text-right p-3">Total</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($orders as $order)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-champagne-dark hover:underline">{{ $order->number }}</a>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $order->user?->name ?? $order->guest_email }}</td>
                            <td class="p-3 text-xs text-muted">{{ $order->created_at->format('M j, Y g:i A') }}</td>
                            <td class="p-3"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                            <td class="p-3"><span class="badge {{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span></td>
                            <td class="p-3 text-right font-medium">Rs. {{ number_format($order->grand_total) }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    @if ($orders->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No orders yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $orders->links() }}</div>
    </div>
@endsection
