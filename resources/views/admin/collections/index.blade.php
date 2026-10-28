@extends('admin.layout')

@section('title', 'Collections — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Collections"
                    subtitle="Curated edits such as Bridal, New Arrivals or seasonal campaigns.">
        <x-slot:actions>
            @can('collections.create')
                <x-admin.button :href="route('admin.collections.create')" size="sm" icon="plus">New collection</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Collections" :value="number_format($stats['total'])" icon="list" />
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="check-circle" />
        <x-admin.stat label="On homepage" :value="number_format($stats['homepage'])" icon="home" />
        <x-admin.stat label="Manual" :value="number_format($stats['manual'])" icon="tag" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <label for="collection-search" class="sr-only">Search collections</label>
            <x-admin.input id="collection-search" name="q" type="search" :value="$filters['q'] ?? ''"
                           placeholder="Search collections" />
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>

        @if (filled($filters['q'] ?? null))
            <x-admin.button :href="route('admin.collections.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Collections">
            <thead>
                <tr>
                    <x-admin.th label="Collection" />
                    <x-admin.th label="Type" />
                    <x-admin.th label="Products" align="right" />
                    <x-admin.th label="Schedule" />
                    <x-admin.th label="Homepage" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>

            <tbody class="divide-y divide-charcoal/5">
                @forelse ($collections as $collection)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            <a href="{{ route('admin.collections.edit', $collection) }}"
                               class="font-medium hover:text-champagne-dark transition-colors">
                                {{ $collection->name }}
                            </a>
                            <div class="text-xs text-muted">/{{ $collection->slug }}</div>
                        </td>

                        <td class="p-3">
                            <span class="badge {{ $collection->type === 'rule_based' ? 'badge-info' : 'badge-charcoal' }}">
                                {{ $collection->type === 'rule_based' ? 'Rule based' : 'Manual' }}
                            </span>
                        </td>

                        <td class="p-3 text-right tabular-nums">
                            @if ($collection->type === 'rule_based')
                                <span class="text-xs text-muted">Automatic</span>
                            @else
                                {{ number_format($collection->products_count) }}
                            @endif
                        </td>

                        <td class="p-3 text-xs text-charcoal-soft">
                            @if ($collection->starts_at || $collection->ends_at)
                                {{ $collection->starts_at?->format('M j, Y') ?? 'Any time' }}
                                →
                                {{ $collection->ends_at?->format('M j, Y') ?? 'No end' }}
                            @else
                                <span class="text-muted">Always on</span>
                            @endif
                        </td>

                        <td class="p-3">
                            @if ($collection->show_on_homepage)
                                <span class="badge badge-success">Yes</span>
                            @else
                                <span class="text-xs text-muted">No</span>
                            @endif
                        </td>

                        <td class="p-3"><x-admin.status-pill :status="$collection->status" /></td>

                        <td class="p-3">
                            <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                @can('collections.update')
                                    <a href="{{ route('admin.collections.edit', $collection) }}"
                                       class="text-xs font-medium text-champagne-dark hover:underline">Edit</a>
                                @endcan

                                <a href="{{ route('collections.show', $collection->slug) }}" target="_blank" rel="noopener"
                                   class="text-xs text-muted hover:text-charcoal hover:underline">Preview</a>

                                @can('collections.delete')
                                    <x-admin.confirm :action="route('admin.collections.destroy', $collection)"
                                                     title="Archive this collection?"
                                                     :message="'“'.$collection->name.'” will be hidden from the storefront.'"
                                                     label="Archive"
                                                     confirm-label="Archive collection" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="list" title="No collections found"
                                           description="Create a curated edit to group products together.">
                                @can('collections.create')
                                    <x-admin.button :href="route('admin.collections.create')" size="sm" icon="plus">New collection</x-admin.button>
                                @endcan
                            </x-admin.empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($collections->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$collections" />
            </div>
        @endif
    </x-admin.card>
@endsection
