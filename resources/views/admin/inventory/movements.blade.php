@extends('admin.layout')

@section('title', 'Inventory Movements — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Inventory Movement Ledger</h1>
            <p class="text-sm text-muted mt-1">Immutable record of every stock change.</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="btn-outline text-sm">Back to Inventory</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">When</th>
                        <th class="text-left p-3">Variant</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Change</th>
                        <th class="text-right p-3">Before → After</th>
                        <th class="text-left p-3">Reason</th>
                        <th class="text-left p-3">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($movements as $movement)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $movement->created_at?->format('M j, Y H:i') }}</td>
                            <td class="p-3">
                                <div class="font-medium">{{ $movement->productVariant?->product?->name ?? '—' }}</div>
                                <div class="text-xs text-muted font-mono">{{ $movement->productVariant?->sku ?? '—' }}</div>
                            </td>
                            <td class="p-3">
                                @php
                                    $dir = $movement->direction === 'in' ? 'bg-emerald-100 text-emerald-800' : ($movement->direction === 'out' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700');
                                @endphp
                                <span class="badge {{ $dir }}">{{ ucwords(str_replace('_', ' ', $movement->type)) }}</span>
                            </td>
                            <td class="p-3 text-right font-medium {{ $movement->quantity_change > 0 ? 'text-emerald-700' : ($movement->quantity_change < 0 ? 'text-rose-700' : '') }}">
                                {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                            </td>
                            <td class="p-3 text-right text-xs text-muted">{{ $movement->previous_stock }} → {{ $movement->new_stock }}</td>
                            <td class="p-3 text-xs text-charcoal-soft">{{ $movement->reason ? ucwords(str_replace('_', ' ', $movement->reason)) : '—' }}</td>
                            <td class="p-3 text-xs text-muted">{{ $movement->actor?->email ?? 'System' }}</td>
                        </tr>
                    @endforeach
                    @if ($movements->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No movements recorded yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $movements->links() }}</div>
    </div>
@endsection
