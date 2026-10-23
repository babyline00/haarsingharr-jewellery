@extends('admin.layout')

@section('title', 'Categories — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Categories</h1>
            <p class="text-sm text-muted mt-1">Organise your catalogue into browsable categories.</p>
        </div>
        @can('categories.create')
        <a href="{{ route('admin.categories.create') }}" class="btn-gold">New Category</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search categories" class="flex-1 min-w-[200px]">
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Parent</th>
                        <th class="text-right p-3">Products</th>
                        <th class="text-right p-3">Order</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($categories as $category)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $category->name }}</a>
                                <div class="text-xs text-muted">/{{ $category->slug }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $category->parent?->name ?? '—' }}</td>
                            <td class="p-3 text-right">{{ $category->products()->count() }}</td>
                            <td class="p-3 text-right">{{ $category->sort_order }}</td>
                            <td class="p-3">
                                <span class="badge {{ $category->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($category->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('categories.delete')
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="post" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($categories->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No categories yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $categories->links() }}</div>
    </div>
@endsection
