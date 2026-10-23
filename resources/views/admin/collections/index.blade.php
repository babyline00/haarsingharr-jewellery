@extends('admin.layout')

@section('title', 'Collections — Haarsingharr Admin')

@section('content')
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
@endsection
