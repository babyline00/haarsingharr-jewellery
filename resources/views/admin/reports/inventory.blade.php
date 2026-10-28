@extends('admin.layout')

@section('title', 'Inventory Report — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Inventory Report</h1>
            <p class="text-sm text-muted mt-1">Stock valuation, low-stock alerts and movement totals.</p>
        </div>
    </div>

    @include('admin.reports._tabs')
<<<<<<< HEAD
    @include('admin.reports._period', ['type' => 'inventory', 'hasCompare' => false])
=======
    @include('admin.reports._period', ['type' => 'inventory'])
>>>>>>> origin/main

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Variants</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">{{ number_format($summary['variants']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Units On Hand</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">{{ number_format($summary['units']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Cost Value</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5">Rs. {{ number_format($summary['value']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Low Stock</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5 text-amber-600">{{ number_format($summary['low_stock']) }}</div>
        </div>
        <div class="card p-4 md:p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Out of Stock</div>
            <div class="font-serif text-2xl md:text-3xl mt-1.5 text-danger">{{ number_format($summary['out_stock']) }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Low stock --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Low / Out of Stock</h2>
                <a href="{{ route('admin.inventory.index') }}" class="text-xs text-champagne-dark hover:underline">Manage inventory →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">SKU</th>
                            <th class="text-left p-3">Product</th>
                            <th class="text-right p-3">On Hand</th>
                            <th class="text-right p-3">Reserved</th>
                            <th class="text-right p-3">Available</th>
                            <th class="text-right p-3">Threshold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($lowStock as $variant)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3 font-mono text-xs">{{ $variant->sku }}</td>
                                <td class="p-3">
                                    @if ($variant->product)
                                        <a href="{{ route('admin.products.edit', $variant->product) }}" class="hover:text-champagne-dark transition-colors">{{ $variant->product->name }}</a>
                                    @else — @endif
                                </td>
                                <td class="p-3 text-right">{{ number_format($variant->stock_quantity) }}</td>
                                <td class="p-3 text-right text-muted">{{ number_format($variant->reserved_stock) }}</td>
                                <td class="p-3 text-right font-medium">{{ number_format($variant->stock_quantity - $variant->reserved_stock) }}</td>
                                <td class="p-3 text-right text-muted">{{ number_format($variant->low_stock_threshold) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No low-stock variants. Everything is well stocked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Movements --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Stock Movements</h2>
                <p class="text-xs text-muted mt-0.5">In the selected period</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Count</th>
                        <th class="text-right p-3">Net Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($movements as $row)
                        <tr>
                            <td class="p-3 capitalize">{{ str_replace('_', ' ', $row->type) }}</td>
                            <td class="p-3 text-right">{{ number_format($row->total) }}</td>
                            <td class="p-3 text-right font-medium {{ $row->net < 0 ? 'text-danger' : 'text-success' }}">{{ $row->net > 0 ? '+' : '' }}{{ number_format($row->net) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-5 text-center text-muted text-sm">No movements recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
