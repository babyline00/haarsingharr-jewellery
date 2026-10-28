@extends('admin.layout')

@section('title', 'Banners — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Banners" subtitle="Promotional images per placement, with scheduling and click tracking.">
        <x-slot:actions>
            @can('banners.create')
                <x-admin.button :href="route('admin.banners.create')" size="sm" icon="plus">New Banner</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <x-admin.stat label="Total" :value="number_format($stats['total'])" icon="image" />
        <x-admin.stat label="Published" :value="number_format($stats['published'])" icon="check-circle" />
        <x-admin.stat label="Scheduled" :value="number_format($stats['scheduled'])" icon="calendar" />
        <x-admin.stat label="Impressions" :value="number_format($stats['impressions'])" icon="eye" />
        <x-admin.stat label="Clicks" :value="number_format($stats['clicks'])" icon="external-link" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[200px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="banner-search" name="q" type="search" :value="request('q')" placeholder="Name or heading" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-48">
            <x-admin.field name="location" label="Placement">
                <x-admin.select id="banner-location" name="location" :selected="$filters['location'] ?? ''"
                                placeholder="All placements"
                                :options="collect($locations)->mapWithKeys(fn ($location) => [$location => ucfirst(str_replace('_', ' ', $location))])->all()" />
            </x-admin.field>
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.banners.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.banners.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="publish">Publish</option>
                <option value="unpublish">Unpublish</option>
                <option value="delete">Delete</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Banners grouped by placement, then sort order">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($banners->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all banners on this page">
                        </th>
                        <x-admin.th label="Placement" />
                        <x-admin.th label="Banner" />
                        <x-admin.th label="Schedule" />
                        <x-admin.th label="Views → clicks" align="right" />
                        <x-admin.th label="CTR" align="right" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($banners as $banner)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $banner->id }}"
                                       x-model="selected" aria-label="Select banner {{ $banner->name }}">
                            </td>
                            <td class="p-3 text-xs uppercase tracking-widest text-muted whitespace-nowrap">
                                {{ ucfirst(str_replace('_', ' ', $banner->location)) }}
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ \App\Support\Media::url($banner->image, \App\Support\Media::SQUARE_PLACEHOLDER) }}"
                                         alt="{{ $banner->alt_text ?: $banner->name }}" class="h-10 w-16 rounded bg-ivory-alt object-cover" loading="lazy" decoding="async">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.banners.edit', $banner) }}" class="font-medium text-champagne-dark hover:underline">{{ $banner->name }}</a>
                                        <span class="block text-xs text-muted">{{ $banner->heading ?: ($banner->link_url ?: '—') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">
                                {{ $banner->starts_at?->format('M j, Y') ?? '—' }} → {{ $banner->ends_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="p-3 text-right tabular-nums whitespace-nowrap">
                                {{ number_format($banner->impressions) }} → {{ number_format($banner->clicks) }}
                            </td>
                            <td class="p-3 text-right tabular-nums">
                                {{ $banner->clickThroughRate() !== null ? $banner->clickThroughRate().'%' : '—' }}
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$banner->is_published ? 'published' : 'unpublished'" :label="$banner->is_published ? 'Published' : 'Draft'" />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @can('banners.update')
                                    <form action="{{ route('admin.banners.move', [$banner, 'up']) }}" method="post" class="inline align-middle">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $banner->name }} up" title="Move up">
                                            <x-admin.icon name="chevron-up" :size="14" />
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.banners.move', [$banner, 'down']) }}" method="post" class="inline align-middle">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $banner->name }} down" title="Move down">
                                            <x-admin.icon name="chevron-down" :size="14" />
                                        </button>
                                    </form>
                                @endcan
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="text-xs text-champagne-dark hover:underline ml-1">Edit</a>
                                @can('banners.delete')
                                    <x-admin.confirm :action="route('admin.banners.destroy', $banner)"
                                                     method="DELETE"
                                                     title="Delete this banner?"
                                                     message="The banner is removed from the storefront immediately."
                                                     label="Delete"
                                                     confirm-label="Delete banner" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty icon="image" title="No banners yet"
                                               description="Create a hero or homepage banner to promote a collection.">
                                    @can('banners.create')
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.banners.create')" size="sm" icon="plus">New Banner</x-admin.button>
                                        </x-slot:action>
                                    @endcan
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($banners->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$banners" />
                </div>
            @endif
        </x-admin.card>
    </form>
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Banners</h1>
            <p class="text-sm text-muted mt-1">Manage hero and promotional imagery across the storefront.</p>
        </div>
        @can('banners.create')
        <a href="{{ route('admin.banners.create') }}" class="btn-gold">New Banner</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <select name="location">
            <option value="">All locations</option>
            @foreach ($locations as $location)
                <option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ ucwords(str_replace('_', ' ', $location)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Banner</th>
                        <th class="text-left p-3">Location</th>
                        <th class="text-left p-3">Schedule</th>
                        <th class="text-right p-3">Order</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($banners as $banner)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ \App\Support\Media::url($banner->image) }}" alt="" class="w-12 h-8 object-cover rounded bg-ivory-alt">
                                    <div>
                                        <a href="{{ route('admin.banners.edit', $banner) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $banner->name }}</a>
                                        @if ($banner->heading) <div class="text-xs text-muted">{{ $banner->heading }}</div> @endif
                                    </div>
                                </div>
                            </td>
                            <td class="p-3"><span class="badge bg-ivory-alt text-charcoal-soft">{{ ucwords(str_replace('_', ' ', $banner->location)) }}</span></td>
                            <td class="p-3 text-xs text-muted">
                                {{ $banner->starts_at?->format('M j, Y') ?? '—' }} → {{ $banner->ends_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="p-3 text-right">{{ $banner->sort_order }}</td>
                            <td class="p-3">
                                <span class="badge {{ $banner->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $banner->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('banners.delete')
                                <form action="{{ route('admin.banners.destroy', $banner) }}" method="post" class="inline" onsubmit="return confirm('Delete this banner?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($banners->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No banners yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $banners->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
