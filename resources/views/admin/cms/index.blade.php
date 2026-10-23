@extends('admin.layout')

@section('title', 'CMS Pages — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">CMS Pages</h1>
            <p class="text-sm text-muted mt-1">Static and marketing pages shown on the storefront.</p>
        </div>
        @can('cms.create')
        <a href="{{ route('admin.pages.create') }}" class="btn-gold">New Page</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search pages" class="flex-1 min-w-[200px]">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['draft', 'published', 'scheduled', 'archived'] as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Title</th>
                        <th class="text-left p-3">Template</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Published</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($pages as $page)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $page->title }}</a>
                                <div class="text-xs text-muted">/{{ $page->slug }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst($page->template) }}</td>
                            <td class="p-3">
                                @php $colors = ['draft' => 'bg-slate-100 text-slate-700', 'published' => 'bg-emerald-100 text-emerald-800', 'scheduled' => 'bg-amber-100 text-amber-800', 'archived' => 'bg-rose-100 text-rose-800']; @endphp
                                <span class="badge {{ $colors[$page->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($page->status) }}</span>
                            </td>
                            <td class="p-3 text-xs text-muted">{{ $page->published_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="text-xs text-muted hover:underline ml-2">View</a>
                                @can('cms.delete')
                                <form action="{{ route('admin.pages.destroy', $page) }}" method="post" class="inline" onsubmit="return confirm('Archive this page?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Archive</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($pages->isEmpty())
                        <tr><td colspan="5" class="p-8 text-center text-muted text-sm">No pages yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $pages->links() }}</div>
    </div>
@endsection
