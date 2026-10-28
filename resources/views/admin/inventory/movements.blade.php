@extends('admin.layout')

@section('title', 'Inventory Movements — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Inventory movement ledger"
                    subtitle="Append-only record of every stock reservation, sale and manual adjustment.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.inventory.index')" variant="outline" size="sm" icon="arrow-left">
                Back to inventory
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    @if ($variant)
        <div class="card p-4 mb-4 flex flex-wrap items-center justify-between gap-3 border-l-4 border-champagne">
            <div>
                <p class="text-xs uppercase tracking-widest text-muted">Filtered ledger</p>
                <p class="font-medium">{{ $variant->product?->name ?? 'Unknown product' }}</p>
                <p class="text-xs text-muted font-mono">{{ $variant->sku }}</p>
            </div>

            <div class="flex items-center gap-2">
                <span class="badge badge-charcoal">{{ $variant->availableStock() }} available</span>
                <x-admin.button :href="route('admin.inventory.movements')" variant="ghost" size="sm">Clear filter</x-admin.button>
            </div>
        </div>
    @endif

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <label for="movement-search" class="sr-only">Search movements</label>
            <x-admin.input id="movement-search" name="q" type="search" :value="$filters['q'] ?? ''"
                           placeholder="Search SKU or product" />
        </div>

        <div class="min-w-[170px]">
            <label for="movement-type" class="sr-only">Movement type</label>
            <x-admin.select id="movement-type" name="type" :selected="$filters['type'] ?? null"
                            :options="collect($types)->mapWithKeys(fn ($type) => [$type => \Illuminate\Support\Str::headline(str_replace('_', ' ', $type))])->all()"
                            placeholder="All types" />
        </div>

        @if (! empty($filters['variant']))
            <input type="hidden" name="variant" value="{{ $filters['variant'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>

        @if (array_filter($filters))
            <x-admin.button :href="route('admin.inventory.movements')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Inventory movements">
            <thead>
                <tr>
                    <x-admin.th label="When" />
                    <x-admin.th label="Variant" />
                    <x-admin.th label="Type" />
                    <x-admin.th label="Change" align="right" />
                    <x-admin.th label="Before → After" align="right" />
                    <x-admin.th label="Reason" />
                    <x-admin.th label="By" />
                </tr>
            </thead>

            <tbody class="divide-y divide-charcoal/5">
                @forelse ($movements as $movement)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3 text-xs text-muted whitespace-nowrap">
                            {{ $movement->created_at?->format('M j, Y H:i') }}
                        </td>

                        <td class="p-3">
                            <a href="{{ route('admin.inventory.movements', ['variant' => $movement->product_variant_id]) }}"
                               class="font-medium hover:text-champagne-dark">
                                {{ $movement->productVariant?->product?->name ?? '—' }}
                            </a>
                            <div class="text-xs text-muted font-mono">{{ $movement->productVariant?->sku ?? '—' }}</div>
                        </td>

                        <td class="p-3">
                            <span class="badge {{ $movement->direction === 'in' ? 'badge-success' : ($movement->direction === 'out' ? 'badge-danger' : 'badge-draft') }}">
                                {{ ucwords(str_replace('_', ' ', $movement->type)) }}
                            </span>
                        </td>

                        <td class="p-3 text-right font-medium tabular-nums {{ $movement->quantity_change > 0 ? 'text-success' : ($movement->quantity_change < 0 ? 'text-danger' : '') }}">
                            {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                        </td>

                        <td class="p-3 text-right text-xs text-muted tabular-nums">
                            {{ $movement->previous_stock }} → {{ $movement->new_stock }}
                        </td>

                        <td class="p-3 text-xs text-charcoal-soft">
                            {{ $movement->reason ? ucwords(str_replace('_', ' ', $movement->reason)) : '—' }}
                            @if ($movement->notes)
                                <div class="text-xs text-muted">{{ $movement->notes }}</div>
                            @endif
                        </td>

                        <td class="p-3 text-xs text-muted">{{ $movement->actor?->email ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="list" title="No movements found"
                                           description="Adjustments, reservations and sales will appear here." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($movements->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$movements" />
            </div>
        @endif
    </x-admin.card>
@endsection
