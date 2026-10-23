@extends('admin.layout')

@section('title', 'Store Locations — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Store Locations</h1>
            <p class="text-sm text-muted mt-1">Physical boutiques shown on the storefront locations page.</p>
        </div>
        @can('store_locations.create')
        <a href="{{ route('admin.store-locations.create') }}" class="btn-gold">New Location</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Location</th>
                        <th class="text-left p-3">Address</th>
                        <th class="text-left p-3">Contact</th>
                        <th class="text-right p-3">Order</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($locations as $location)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.store-locations.edit', $location) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $location->name }}</a>
                                @if ($location->is_main) <span class="badge bg-champagne/20 text-champagne-dark ml-1">Main</span> @endif
                                <div class="text-xs text-muted">/{{ $location->slug }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft max-w-xs">{{ Str::limit($location->address, 70) }}</td>
                            <td class="p-3 text-xs text-muted">
                                {{ $location->phone ?: '—' }}
                                @if ($location->email) <div>{{ $location->email }}</div> @endif
                            </td>
                            <td class="p-3 text-right">{{ $location->sort_order }}</td>
                            <td class="p-3">
                                <span class="badge {{ $location->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($location->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.store-locations.edit', $location) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('store_locations.delete')
                                <form action="{{ route('admin.store-locations.destroy', $location) }}" method="post" class="inline" onsubmit="return confirm('Delete this location?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($locations->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No store locations yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $locations->links() }}</div>
    </div>
@endsection
