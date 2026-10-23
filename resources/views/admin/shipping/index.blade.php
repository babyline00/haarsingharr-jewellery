@extends('admin.layout')

@section('title', 'Shipping — Haarsingharr Admin')

@php
    $statusColors = [
        'pending' => 'bg-slate-100 text-slate-700',
        'picked_up' => 'bg-blue-100 text-blue-800',
        'in_transit' => 'bg-indigo-100 text-indigo-800',
        'out_for_delivery' => 'bg-amber-100 text-amber-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-rose-100 text-rose-800',
        'returned' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Shipping</h1>
            <p class="text-sm text-muted mt-1">Rates, free-shipping threshold and courier activity.</p>
        </div>
    </div>

    @can('shipping.update')
    <form action="{{ route('admin.shipping.store') }}" method="post" class="mb-6">
        @csrf
        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Rates &amp; Rules</h2>
            <div class="grid md:grid-cols-3 gap-4">
                @foreach ($settings as $setting)
                    <div>
                        @if ($setting['type'] === 'boolean')
                            <input type="hidden" name="shipping[{{ $setting['key'] }}]" value="0">
                            <label class="flex items-center gap-2 text-sm mt-6">
                                <input type="checkbox" name="shipping[{{ $setting['key'] }}]" value="1" @checked((bool) $setting['value'])>
                                {{ $setting['label'] }}
                            </label>
                        @else
                            <label for="ship-{{ $setting['key'] }}">{{ $setting['label'] }}</label>
                            <input
                                type="{{ $setting['type'] === 'number' ? 'number' : 'text' }}"
                                @if ($setting['type'] === 'number') step="0.01" @endif
                                id="ship-{{ $setting['key'] }}"
                                name="shipping[{{ $setting['key'] }}]"
                                value="{{ $setting['value'] }}"
                            >
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-5">
                <button type="submit" class="btn-gold">Save Shipping Settings</button>
            </div>
        </div>
    </form>
    @endcan

    <h2 class="font-serif text-lg mb-3">Recent Shipments</h2>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Courier</th>
                        <th class="text-left p-3">Tracking</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Cost</th>
                        <th class="text-left p-3">Shipped</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($shipments as $shipment)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($shipment->order)
                                    <a href="{{ route('admin.orders.show', $shipment->order) }}" class="font-medium text-champagne-dark hover:underline">{{ $shipment->order->number }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $shipment->courier }} @if ($shipment->method) <span class="text-xs text-muted">/ {{ $shipment->method }}</span> @endif</td>
                            <td class="p-3 font-mono text-xs">{{ $shipment->tracking_number }}</td>
                            <td class="p-3"><span class="badge {{ $statusColors[$shipment->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span></td>
                            <td class="p-3 text-right">{{ number_format($shipment->shipping_cost, 2) }}</td>
                            <td class="p-3 text-xs text-muted">{{ $shipment->shipped_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    @if ($shipments->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No shipments recorded yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $shipments->links() }}</div>
    </div>
@endsection
