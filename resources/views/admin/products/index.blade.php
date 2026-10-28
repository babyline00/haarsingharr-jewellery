@extends('admin.layout')

@section('title', 'Products — Haarsingharr Admin')

@section('content')
    @php
        $statusOptions = collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all();
        $categoryOptions = $categories->pluck('name', 'slug')->all();
        $stockOptions = ['low' => 'Low stock', 'out' => 'Out of stock'];
        $hasFilters = filled($filters['q'] ?? null)
            || filled($filters['status'] ?? null)
            || filled($filters['category'] ?? null)
            || filled($filters['stock'] ?? null);
    @endphp

    <x-admin.header title="Products" subtitle="Manage the catalogue, pricing, variants and stock.">
        <x-slot:actions>
            @can('products.create')
                <x-admin.button :href="route('admin.products.import')" variant="outline" size="sm" icon="upload">
                    Import CSV
                </x-admin.button>
            @endcan

            <x-admin.button :href="route('admin.products.export', request()->query())" variant="outline" size="sm" icon="download">
                Export
            </x-admin.button>

            @can('products.create')
                <x-admin.button :href="route('admin.products.create')" size="sm" icon="plus">
                    New Product
                </x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="All products" :value="number_format($stats['total'])" icon="package"
                       :href="route('admin.products.index')" />
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="check-circle"
                       :href="route('admin.products.index', ['status' => 'active'])" />
        <x-admin.stat label="Drafts" :value="number_format($stats['draft'])" icon="edit"
                       :href="route('admin.products.index', ['status' => 'draft'])" />
        <x-admin.stat label="Low stock" :value="number_format($stats['low_stock'])" icon="alert-triangle"
                       :href="route('admin.products.index', ['stock' => 'low'])" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <label for="product-search" class="sr-only">Search products</label>
            <x-admin.input id="product-search" name="q" type="search" :value="$filters['q'] ?? ''"
                           placeholder="Search name, SKU or description" />
        </div>

        <div class="min-w-[150px]">
            <label for="product-status" class="sr-only">Status</label>
            <x-admin.select id="product-status" name="status" :selected="$filters['status'] ?? null"
                            :options="$statusOptions" placeholder="All statuses" />
        </div>

        <div class="min-w-[170px]">
            <label for="product-category" class="sr-only">Category</label>
            <x-admin.select id="product-category" name="category" :selected="$filters['category'] ?? null"
                            :options="$categoryOptions" placeholder="All categories" />
        </div>

        <div class="min-w-[150px]">
            <label for="product-stock" class="sr-only">Stock level</label>
            <x-admin.select id="product-stock" name="stock" :selected="$filters['stock'] ?? null"
                            :options="$stockOptions" placeholder="Any stock level" />
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>

        @if ($hasFilters)
            <x-admin.button :href="route('admin.products.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form id="products-bulk-form" action="{{ route('admin.products.bulk') }}" method="post"
          class="card p-3 mb-4 flex flex-wrap items-center gap-2">
        @csrf

        <span class="text-xs text-muted" id="products-bulk-count">0 selected</span>

        <label for="products-bulk-action" class="sr-only">Bulk action</label>
        <select id="products-bulk-action" name="action" class="text-sm w-auto" required>
            <option value="">Bulk action…</option>
            @can('products.update')
                <option value="activate">Set active</option>
                <option value="draft">Set draft</option>
                <option value="archive">Archive</option>
            @endcan
            @can('products.delete')
                <option value="delete">Archive (soft delete)</option>
            @endcan
        </select>

        <button type="submit" id="products-bulk-apply" class="btn-outline btn-sm" disabled>Apply to selected</button>

        <span class="text-xs text-muted ml-auto">Select rows with the checkboxes, then apply an action.</span>
    </form>

<<<<<<< HEAD
    <x-admin.card :padding="false">
        <x-admin.table caption="Products">
            <thead>
                <tr>
                    <th scope="col" class="w-10 p-3">
                        <input type="checkbox" id="products-select-all" aria-label="Select all products on this page">
                    </th>
                    <x-admin.th label="Product" />
                    <x-admin.th label="Category" />
                    <x-admin.th label="Price" align="right" />
                    <x-admin.th label="Stock" align="right" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>

            <tbody class="divide-y divide-charcoal/5">
                @forelse ($products as $product)
                    @php
                        $stock = $product->availableStock();
                        $threshold = (int) ($product->variants->min('low_stock_threshold') ?? 5);
                        $stockState = $stock <= 0 ? 'out_of_stock' : ($stock <= $threshold ? 'low_stock' : null);
                    @endphp

                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3 align-top">
                            <input type="checkbox" class="product-row-check" name="ids[]" value="{{ $product->id }}"
                                   form="products-bulk-form" aria-label="Select {{ $product->name }}">
                        </td>

                        <td class="p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                    @if ($path = $product->primaryImagePath())
                                        <img src="{{ \App\Support\Media::url($path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover" loading="lazy" decoding="async">
                                    @else
                                        <img src="{{ asset('images/placeholder-square.svg') }}" alt="No product image" class="w-full h-full object-cover">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="font-medium hover:text-champagne-dark transition-colors">
                                        {{ $product->name }}
                                    </a>
                                    <div class="text-xs text-muted">
                                        SKU: {{ $product->sku ?? '—' }}
                                        @if ($product->variants->isNotEmpty())
                                            · {{ $product->variants->count() }} variant(s)
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="p-3 text-charcoal-soft">{{ $product->category?->name ?? '—' }}</td>

                        <td class="p-3 text-right font-medium">
                            <x-admin.money :amount="$product->currentPrice()" />
                        </td>

                        <td class="p-3 text-right">
                            <span class="tabular-nums font-medium">{{ number_format($stock) }}</span>
                            @if ($stockState)
                                <x-admin.status-pill :status="$stockState"
                                                     :label="$stockState === 'out_of_stock' ? 'Out' : 'Low'"
                                                     class="ml-1" />
                            @endif
                        </td>

                        <td class="p-3">
                            <x-admin.status-pill :status="$product->status->value" :label="$product->status->label()" />
                        </td>

                        <td class="p-3">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                @can('products.update')
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="text-xs font-medium text-champagne-dark hover:underline">Edit</a>
=======
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
>>>>>>> origin/main
                                @endcan

                                <a href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener"
                                   class="text-xs text-muted hover:text-charcoal hover:underline">Preview</a>

                                @can('products.create')
                                    <form action="{{ route('admin.products.duplicate', $product) }}" method="post" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-charcoal-soft hover:text-champagne-dark hover:underline">
                                            Duplicate
                                        </button>
                                    </form>
                                @endcan

                                @can('products.delete')
                                    <x-admin.confirm :action="route('admin.products.destroy', $product)"
                                                     title="Archive this product?"
                                                     :message="'“'.$product->name.'” will be hidden from the storefront. You can restore it later.'"
                                                     label="Archive"
                                                     confirm-label="Archive product" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="package" title="No products found"
                                           description="Try clearing the filters, or create your first product.">
                                @can('products.create')
                                    <x-admin.button :href="route('admin.products.create')" size="sm" icon="plus">New Product</x-admin.button>
                                @endcan
                            </x-admin.empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($products->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$products" />
            </div>
        @endif
    </x-admin.card>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checks = Array.from(document.querySelectorAll('.product-row-check'));
            const selectAll = document.getElementById('products-select-all');
            const apply = document.getElementById('products-bulk-apply');
            const count = document.getElementById('products-bulk-count');

            function refresh() {
                const selected = checks.filter(function (checkbox) { return checkbox.checked; }).length;
                if (count) count.textContent = selected + ' selected';
                if (apply) apply.disabled = selected === 0;
                if (selectAll) {
                    selectAll.checked = selected > 0 && selected === checks.length;
                    selectAll.indeterminate = selected > 0 && selected < checks.length;
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checks.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
                    refresh();
                });
            }

            checks.forEach(function (checkbox) {
                checkbox.addEventListener('change', refresh);
            });

            refresh();
        });
    </script>
@endpush
