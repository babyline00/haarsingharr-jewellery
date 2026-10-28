@extends('admin.layout')

@section('title', 'Homepage Builder — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Homepage Builder"
                    subtitle="Compose the storefront homepage from ordered sections. Preview renders exactly as customers see it.">
        <x-slot:actions>
            <a href="{{ route('home', ['preview' => 1]) }}" target="_blank" rel="noopener" class="btn-outline btn-sm">Preview homepage</a>
            @can('homepage.create')
                <x-admin.button :href="route('admin.homepage-sections.create')" size="sm" icon="plus">New Section</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-3 gap-3 mb-5">
        <x-admin.stat label="Total sections" :value="number_format($stats['total'])" icon="layout" />
        <x-admin.stat label="Published" :value="number_format($stats['published'])" icon="check-circle" />
        <x-admin.stat label="Drafts" :value="number_format($stats['draft'])" icon="file-text" />
    </div>

    <form method="POST" action="{{ route('admin.homepage-sections.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[200px]" aria-label="Bulk action">
                <option value="publish">Publish</option>
                <option value="unpublish">Set as draft</option>
                <option value="duplicate">Duplicate</option>
                <option value="delete">Delete</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Homepage sections, ordered">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($sections->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all homepage sections on this page">
                        </th>
                        <x-admin.th label="Key" />
                        <x-admin.th label="Title" />
                        <x-admin.th label="View" />
                        <x-admin.th label="Schedule" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($sections as $section)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $section->id }}"
                                       x-model="selected" aria-label="Select homepage section {{ $section->key }}">
                            </td>
                            <td class="p-3">
                                <a href="{{ route('admin.homepage-sections.edit', $section) }}"
                                   class="font-mono text-xs font-medium text-champagne-dark hover:underline">{{ $section->key }}</a>
                            </td>
                            <td class="p-3">
                                <div class="font-medium">{{ $section->title ?: '—' }}</div>
                                @if ($section->subtitle) <div class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($section->subtitle, 60) }}</div> @endif
                            </td>
                            <td class="p-3 text-xs text-charcoal-soft">{{ $section->view ?: '—' }}</td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">
                                {{ $section->starts_at?->format('M j, Y') ?? '—' }} → {{ $section->ends_at?->format('M j, Y') ?? '—' }}
                                @if ((! $section->starts_at || $section->starts_at->isPast()) && (! $section->ends_at || $section->ends_at->isFuture()))
                                    <span class="block text-emerald-700">Live window</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$section->is_published ? 'published' : 'unpublished'" :label="$section->is_published ? 'Published' : 'Draft'" />
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @can('homepage.update')
                                    <form action="{{ route('admin.homepage-sections.move', [$section, 'up']) }}" method="post" class="inline align-middle">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move section {{ $section->key }} up" title="Move up">
                                            <x-admin.icon name="chevron-up" :size="14" />
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.homepage-sections.move', [$section, 'down']) }}" method="post" class="inline align-middle">
                                        @csrf
                                        <button type="submit" class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move section {{ $section->key }} down" title="Move down">
                                            <x-admin.icon name="chevron-down" :size="14" />
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.homepage-sections.bulk') }}" method="post" class="inline align-middle ml-1">
                                        @csrf
                                        <input type="hidden" name="ids[]" value="{{ $section->id }}">
                                        <input type="hidden" name="action" value="{{ $section->is_published ? 'unpublish' : 'publish' }}">
                                        <button type="submit" class="text-xs text-charcoal-soft hover:underline"
                                                aria-label="{{ $section->is_published ? 'Set '.e($section->key).' as draft' : 'Publish section '.e($section->key) }}">
                                            {{ $section->is_published ? 'Set draft' : 'Publish' }}
                                        </button>
                                    </form>
                                @endcan

                                @can('homepage.create')
                                    <form action="{{ route('admin.homepage-sections.duplicate', $section) }}" method="post" class="inline align-middle ml-1">
                                        @csrf
                                        <button type="submit" class="text-xs text-charcoal-soft hover:underline"
                                                aria-label="Duplicate section {{ $section->key }}">Duplicate</button>
                                    </form>
                                @endcan

                                <a href="{{ route('admin.homepage-sections.edit', $section) }}" class="text-xs text-champagne-dark hover:underline ml-1">Edit</a>

                                @can('homepage.delete')
                                    <x-admin.confirm :action="route('admin.homepage-sections.destroy', $section)"
                                                     method="DELETE"
                                                     title="Delete this section?"
                                                     message="Customers will stop seeing this section immediately."
                                                     label="Delete"
                                                     confirm-label="Delete section" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-admin.empty icon="layout" title="No homepage sections yet"
                                               description="Sections render top to bottom on the storefront homepage. Add the first one.">
                                    @can('homepage.create')
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.homepage-sections.create')" size="sm" icon="plus">New Section</x-admin.button>
                                        </x-slot:action>
                                    @endcan
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($sections->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$sections" />
                </div>
            @endif
        </x-admin.card>
    </form>
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Homepage Builder</h1>
            <p class="text-sm text-muted mt-1">Compose the storefront homepage from ordered sections.</p>
        </div>
        @can('homepage.create')
        <a href="{{ route('admin.homepage-sections.create') }}" class="btn-gold">New Section</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-right p-3">Order</th>
                        <th class="text-left p-3">Key</th>
                        <th class="text-left p-3">Title</th>
                        <th class="text-left p-3">View</th>
                        <th class="text-left p-3">Schedule</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($sections as $section)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-right text-muted">{{ $section->sort_order }}</td>
                            <td class="p-3">
                                <a href="{{ route('admin.homepage-sections.edit', $section) }}" class="font-mono text-xs font-medium hover:text-champagne-dark transition-colors">{{ $section->key }}</a>
                            </td>
                            <td class="p-3">
                                <div class="font-medium">{{ $section->title ?: '—' }}</div>
                                @if ($section->subtitle) <div class="text-xs text-muted">{{ Str::limit($section->subtitle, 60) }}</div> @endif
                            </td>
                            <td class="p-3 text-xs text-charcoal-soft">{{ $section->view ?: '—' }}</td>
                            <td class="p-3 text-xs text-muted">
                                {{ $section->starts_at?->format('M j, Y') ?? '—' }} → {{ $section->ends_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="p-3">
                                <span class="badge {{ $section->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $section->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.homepage-sections.edit', $section) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('homepage.delete')
                                <form action="{{ route('admin.homepage-sections.destroy', $section) }}" method="post" class="inline" onsubmit="return confirm('Delete this section?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($sections->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No homepage sections yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $sections->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
