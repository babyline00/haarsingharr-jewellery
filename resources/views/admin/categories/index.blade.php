@extends('admin.layout')

@section('title', 'Categories — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Categories"
                    subtitle="Organise the catalogue into a browsable tree. Order controls the storefront navigation.">
        <x-slot:actions>
            @can('categories.create')
                <x-admin.button :href="route('admin.categories.create')" size="sm" icon="plus">New category</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Categories" :value="number_format($stats['total'])" icon="grid" />
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="check-circle" />
        <x-admin.stat label="Top level" :value="number_format($stats['roots'])" icon="list" />
        <x-admin.stat label="Categorised products" :value="number_format($stats['products'])" icon="package" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <label for="category-search" class="sr-only">Search categories</label>
            <x-admin.input id="category-search" name="q" type="search" :value="$filters['q'] ?? ''"
                           placeholder="Search categories" />
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>

        @if (filled($filters['q'] ?? null))
            <x-admin.button :href="route('admin.categories.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form id="categories-bulk-form" action="{{ route('admin.categories.bulk') }}" method="post"
          class="card p-3 mb-4 flex flex-wrap items-center gap-2">
        @csrf

        <span class="text-xs text-muted" id="categories-bulk-count">0 selected</span>

        <label for="categories-bulk-action" class="sr-only">Bulk action</label>
        <select id="categories-bulk-action" name="action" class="text-sm w-auto" required>
            <option value="">Bulk action…</option>
            @can('categories.update')
                <option value="activate">Set active</option>
                <option value="inactive">Set inactive</option>
            @endcan
            @can('categories.delete')
                <option value="delete">Delete</option>
            @endcan
        </select>

        <button type="submit" id="categories-bulk-apply" class="btn-outline btn-sm" disabled>Apply to selected</button>

        <span class="text-xs text-muted ml-auto">Deletion is blocked while a category has products or subcategories.</span>
    </form>

    <x-admin.card :padding="false">
        <x-admin.table caption="Category tree">
            <thead>
                <tr>
                    <th scope="col" class="w-10 p-3">
                        <input type="checkbox" id="categories-select-all" aria-label="Select all categories">
                    </th>
                    <x-admin.th label="Category" />
                    <x-admin.th label="Parent" />
                    <x-admin.th label="Products" align="right" />
                    <x-admin.th label="Order" align="right" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>

            <tbody class="divide-y divide-charcoal/5">
                @forelse ($rows as $row)
                    @php
                        $category = $row['category'];
                        $depth = $row['depth'];
                        $productCount = $category->products_count + $category->subcategory_products_count;
                    @endphp

                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3 align-top">
                            <input type="checkbox" class="category-row-check" name="ids[]" value="{{ $category->id }}"
                                   form="categories-bulk-form" aria-label="Select {{ $category->name }}">
                        </td>

                        <td class="p-3">
                            <div style="padding-left: {{ $depth * 1.35 }}rem" class="flex items-start gap-2">
                                @if ($depth > 0)
                                    <span class="text-muted/40 mt-0.5" aria-hidden="true">↳</span>
                                @endif

                                <div class="min-w-0">
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="font-medium hover:text-champagne-dark transition-colors">
                                        {{ $category->name }}
                                    </a>
                                    <div class="text-xs text-muted">/{{ $category->slug }}</div>
                                    <div class="mt-1">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] {{ $category->show_on_homepage ? 'bg-emerald-50 text-emerald-700' : 'bg-charcoal/5 text-muted' }}">
                                            {{ $category->show_on_homepage ? 'On homepage' : 'Hidden from homepage' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="p-3 text-charcoal-soft">{{ $category->parent?->name ?? '—' }}</td>

                        <td class="p-3 text-right tabular-nums">
                            @if ($productCount > 0)
                                <a href="{{ route('admin.products.index', ['category' => $category->slug]) }}"
                                   class="text-champagne-dark hover:underline">{{ $productCount }}</a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>

                        <td class="p-3">
                            <div class="flex items-center justify-end gap-1">
                                <span class="text-xs text-muted tabular-nums mr-1">{{ $category->sort_order }}</span>

                                @can('categories.update')
                                    <form action="{{ route('admin.categories.move', [$category, 'up']) }}" method="post">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $category->name }} up" title="Move up">
                                            <x-admin.icon name="chevron-up" :size="14" />
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.categories.move', [$category, 'down']) }}" method="post">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $category->name }} down" title="Move down">
                                            <x-admin.icon name="chevron-down" :size="14" />
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>

                        <td class="p-3">
                            <x-admin.status-pill :status="$category->status" />
                        </td>

                        <td class="p-3">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                @can('categories.update')
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       class="text-xs font-medium text-champagne-dark hover:underline">Edit</a>
                                @endcan

                                <a href="{{ route('categories.show', $category->slug) }}" target="_blank" rel="noopener"
                                   class="text-xs text-muted hover:text-charcoal hover:underline">Preview</a>

                                @can('categories.delete')
                                    <x-admin.confirm :action="route('admin.categories.destroy', $category)"
                                                     title="Delete this category?"
                                                     :message="'“'.$category->name.'” can only be deleted when it has no products or subcategories.'"
                                                     label="Delete"
                                                     confirm-label="Delete category" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="grid" title="No categories found"
                                           description="Create a category to start organising the catalogue.">
                                @can('categories.create')
                                    <x-admin.button :href="route('admin.categories.create')" size="sm" icon="plus">New category</x-admin.button>
                                @endcan
                            </x-admin.empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.card>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checks = Array.from(document.querySelectorAll('.category-row-check'));
            const selectAll = document.getElementById('categories-select-all');
            const apply = document.getElementById('categories-bulk-apply');
            const count = document.getElementById('categories-bulk-count');

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

            checks.forEach(function (checkbox) { checkbox.addEventListener('change', refresh); });
            refresh();
        });
    </script>
@endpush
