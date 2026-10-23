@extends('admin.layout')

@section('title', 'Banners — Haarsingharr Admin')

@section('content')
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
@endsection
