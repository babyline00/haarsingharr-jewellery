@extends('admin.layout')

@section('title', 'Products — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Products</h1>
            <p class="text-sm text-muted mt-1">Manage your catalogue, variants, pricing &amp; inventory.</p>
        </div>
        @can('products.create')
        <a href="{{ route('admin.products.create') }}" class="btn-gold">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Product
        </a>
        @endcan
    </div>

    {{-- Filters --}}
    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search by name, SKU" class="flex-1 min-w-[200px]">
        <select name="status" class="text-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="category" class="text-sm">
            <option value="">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->slug }}" @selected(($filters['category'] ?? '') === $cat->slug)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Product</th>
                        <th class="text-left p-3">Category</th>
                        <th class="text-right p-3">Price</th>
                        <th class="text-right p-3">Stock</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($products as $product)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                        @if ($path = $product->primaryImagePath())
                                            <img src="{{ \App\Support\Media::url($path) }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $product->name }}</a>
                                        <div class="text-xs text-muted">SKU: {{ $product->sku ?? '—' }}</div>
                                        @if ($product->videos_count > 0)
                                            <div class="text-xs text-champagne-dark mt-0.5">{{ $product->videos_count }} video{{ $product->videos_count === 1 ? '' : 's' }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $product->category?->name ?? '—' }}</td>
                            <td class="p-3 text-right font-medium">Rs. {{ number_format($product->currentPrice()) }}</td>
                            <td class="p-3 text-right">
                                @php
                                    $stock = $product->availableStock();
                                    $threshold = $product->variants->min('low_stock_threshold') ?? 5;
                                @endphp
                                @if ($stock <= 0)
                                    <span class="badge bg-rose-100 text-rose-800">Out of stock</span>
                                @elseif ($stock <= $threshold)
                                    <span class="badge bg-amber-100 text-amber-800">{{ $stock }} low</span>
                                @else
                                    <span class="badge bg-emerald-100 text-emerald-800">{{ $stock }}</span>
                                @endif
                            </td>
                            <td class="p-3"><span class="badge {{ $product->status->color() }}">{{ $product->status->label() }}</span></td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.products.edit', $product) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('products.delete')
                                <form action="{{ route('admin.products.destroy', $product) }}" method="post" class="inline" onsubmit="return confirm('Archive this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Archive</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($products->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No products yet. Click "New Product" to create one.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">
            {{ $products->links() }}
        </div>
    </div>
@endsection
