@extends('admin.layout')

@section('title', 'Homepage Builder — Haarsingharr Admin')

@section('content')
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
@endsection
