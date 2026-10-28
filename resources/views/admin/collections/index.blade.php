@extends('admin.layout')

@section('title', 'Collections — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
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
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Collections</h1>
            <p class="text-sm text-muted mt-1">Curated groupings such as Bridal, New Arrivals or seasonal edits.</p>
        </div>
        @can('collections.create')
        <a href="{{ route('admin.collections.create') }}" class="btn-gold">New Collection</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search collections" class="flex-1 min-w-[200px]">
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Products</th>
                        <th class="text-left p-3">Homepage</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($collections as $collection)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $collection->name }}</a>
                                <div class="text-xs text-muted">/{{ $collection->slug }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst(str_replace('_', ' ', $collection->type)) }}</td>
                            <td class="p-3 text-right">{{ $collection->products()->count() }}</td>
                            <td class="p-3">{!! $collection->show_on_homepage ? '<span class="badge bg-emerald-100 text-emerald-800">Yes</span>' : '<span class="text-muted text-xs">No</span>' !!}</td>
                            <td class="p-3">
                                <span class="badge {{ $collection->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ ucfirst($collection->status) }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.collections.edit', $collection) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('collections.delete')
                                <form action="{{ route('admin.collections.destroy', $collection) }}" method="post" class="inline" onsubmit="return confirm('Archive this collection?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Archive</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($collections->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No collections yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $collections->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
