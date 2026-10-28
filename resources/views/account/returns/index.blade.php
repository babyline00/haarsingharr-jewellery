@extends('layouts.storefront')

@section('title', 'Returns & Exchanges — Haarsingharr')

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
    <section class="py-12">
        <div class="container-haars max-w-4xl">
            <div class="flex items-center justify-between mb-8">
                <h1 class="font-serif text-3xl md:text-4xl">Returns &amp; Exchanges</h1>
                <a href="{{ route('account.dashboard') }}" class="btn-ghost text-sm">Back to Account</a>
            </div>

            @if (session('success'))
                <div class="bg-success/10 border border-success/30 text-success px-4 py-3 rounded-md text-sm mb-4">{{ session('success') }}</div>
            @endif

            <div class="card overflow-hidden mb-8">
                <div class="p-5 pb-3"><h2 class="font-serif text-xl">Your requests</h2></div>
                @if ($returns->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                                <tr>
                                    <th class="text-left p-3">Request</th>
                                    <th class="text-left p-3">Order</th>
                                    <th class="text-left p-3">Type</th>
                                    <th class="text-left p-3">Status</th>
                                    <th class="text-right p-3">Amount</th>
                                    <th class="text-left p-3">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-charcoal/5">
                                @foreach ($returns as $return)
                                    <tr>
                                        <td class="p-3 font-mono text-xs">{{ $return->number }}</td>
                                        <td class="p-3">
                                            @if ($return->order)
                                                <a href="{{ route('account.orders.show', $return->order->number) }}" class="text-champagne-dark hover:underline">{{ $return->order->number }}</a>
                                            @else — @endif
                                        </td>
                                        <td class="p-3">{{ ucfirst($return->type) }}</td>
                                        <td class="p-3"><span class="badge {{ $statusColors[$return->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($return->status) }}</span></td>
                                        <td class="p-3 text-right">{{ $return->refund_amount ? number_format($return->refund_amount, 2) : '—' }}</td>
                                        <td class="p-3 text-xs text-muted">{{ $return->created_at?->format('M j, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t border-charcoal/5">{{ $returns->links() }}</div>
                @else
                    <p class="p-6 text-sm text-muted text-center">You have no return or exchange requests.</p>
                @endif
            </div>

            @php $orders = auth()->user()->orders()->latest()->limit(25)->get(); @endphp
            <div class="card p-6">
                <h2 class="font-serif text-xl mb-1">Start a request</h2>
                <p class="text-sm text-muted mb-4">Tell us what you'd like to return or exchange and we'll respond within 24 hours.</p>

                @if ($orders->isEmpty())
                    <p class="text-sm text-muted">You have no orders eligible for a return yet.</p>
                @else
                    <form action="{{ route('account.returns.store') }}" method="post" class="space-y-4">
                        @csrf
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="order_id">Order *</label>
                                <select id="order_id" name="order_id" required>
                                    @foreach ($orders as $order)
                                        <option value="{{ $order->id }}" @selected(old('order_id') == $order->id)>{{ $order->number }} — {{ $order->created_at->format('j M Y') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="type">Request type *</label>
                                <select id="type" name="type" required>
                                    <option value="return" @selected(old('type') === 'return')>Return</option>
                                    <option value="exchange" @selected(old('type') === 'exchange')>Exchange</option>
                                    <option value="refund" @selected(old('type') === 'refund')>Refund</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="reason">Reason *</label>
                            <textarea id="reason" name="reason" rows="4" required>{{ old('reason') }}</textarea>
                            @error('reason') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="quantity">Quantity</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="{{ old('quantity', 1) }}">
                        </div>
                        <button type="submit" class="btn-gold">Submit Request</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
