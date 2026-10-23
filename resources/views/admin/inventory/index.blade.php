@extends('admin.layout')

@section('title', 'Inventory — Haarsingharr Admin')

@php
    $reasons = [
        'physical_count'    => 'Physical count',
        'received_shipment' => 'Received shipment',
        'damaged'           => 'Damaged',
        'lost'              => 'Lost',
        'returned'          => 'Returned',
        'transfer_in'       => 'Transfer in',
        'transfer_out'      => 'Transfer out',
        'correction'        => 'Correction',
        'manual'            => 'Manual adjustment',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Inventory</h1>
            <p class="text-sm text-muted mt-1">Stock levels per variant. Adjustments are written to the movement ledger.</p>
        </div>
        <a href="{{ route('admin.inventory.movements') }}" class="btn-outline text-sm">Movement Ledger</a>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search SKU or product" class="flex-1 min-w-[200px]">
        <select name="filter">
            <option value="">All stock</option>
            <option value="low_stock" @selected(($filters['filter'] ?? '') === 'low_stock')>Low stock</option>
            <option value="out_of_stock" @selected(($filters['filter'] ?? '') === 'out_of_stock')>Out of stock</option>
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Variant</th>
                        <th class="text-right p-3">On hand</th>
                        <th class="text-right p-3">Reserved</th>
                        <th class="text-right p-3">Available</th>
                        <th class="text-right p-3">Threshold</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Adjust</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($variants as $variant)
                        @php
                            $available = $variant->stock_quantity - $variant->reserved_stock;
                            $threshold = $variant->low_stock_threshold ?? 5;
                        @endphp
                        <tr class="hover:bg-ivory-alt/30 transition-colors align-top">
                            <td class="p-3">
                                <div class="font-medium">{{ $variant->product?->name ?? '—' }}</div>
                                <div class="text-xs text-muted font-mono">{{ $variant->sku }}</div>
                            </td>
                            <td class="p-3 text-right font-medium">{{ $variant->stock_quantity }}</td>
                            <td class="p-3 text-right text-charcoal-soft">{{ $variant->reserved_stock }}</td>
                            <td class="p-3 text-right">{{ $available }}</td>
                            <td class="p-3 text-right text-muted">{{ $threshold }}</td>
                            <td class="p-3">
                                @if ($available <= 0)
                                    <span class="badge bg-rose-100 text-rose-800">Out of stock</span>
                                @elseif ($variant->stock_quantity <= $threshold)
                                    <span class="badge bg-amber-100 text-amber-800">Low stock</span>
                                @else
                                    <span class="badge bg-emerald-100 text-emerald-800">In stock</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @can('inventory.update')
                                <form action="{{ route('admin.inventory.adjust') }}" method="post" class="flex flex-wrap items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="product_variant_id" value="{{ $variant->id }}">
                                    <input type="number" name="new_stock" min="0" value="{{ $variant->stock_quantity }}" class="w-20 text-sm" required>
                                    <select name="reason" class="text-sm">
                                        @foreach ($reasons as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="notes" placeholder="Note" class="w-28 text-sm">
                                    <button type="submit" class="btn-outline text-xs px-2 py-1" onclick="return confirm('Apply this stock adjustment?')">Apply</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($variants->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No variants found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $variants->links() }}</div>
    </div>
@endsection
