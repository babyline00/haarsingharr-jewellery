@extends('admin.layout')

@section('title', 'SEO — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">SEO</h1>
            <p class="text-sm text-muted mt-1">Default metadata, analytics and URL redirects.</p>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="btn-ghost text-sm">Back to Settings</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div>
            @can('seo.update')
            <form action="{{ route('admin.settings.update') }}" method="post">
                @csrf
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Metadata &amp; Analytics</h2>
                    <div class="space-y-4">
                        @include('admin.settings._fields', ['module' => 'seo', 'fields' => $groups['seo'] ?? []])
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn-gold">Save SEO Settings</button>
                </div>
            </form>
            @endcan
        </div>

        <div class="space-y-4">
            @can('seo.create')
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Add Redirect</h2>
                <form action="{{ route('admin.seo.redirects.store') }}" method="post" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-[1fr_auto] gap-3">
                        <div>
                            <label for="source_url">From path *</label>
                            <input type="text" id="source_url" name="source_url" required value="{{ old('source_url') }}" placeholder="/old-page">
                            @error('source_url') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="status_code">Code</label>
                            <select id="status_code" name="status_code">
                                @foreach ([301, 302, 307, 308] as $code)
                                    <option value="{{ $code }}" @selected(old('status_code', 301) == $code)>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="destination_url">To URL *</label>
                        <input type="text" id="destination_url" name="destination_url" required value="{{ old('destination_url') }}" placeholder="/new-page or https://…">
                        @error('destination_url') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-gold">Save Redirect</button>
                </form>
            </div>
            @endcan

            <div class="card overflow-hidden">
                <div class="p-5 pb-3"><h2 class="font-serif text-lg">Redirects</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                            <tr>
                                <th class="text-left p-3">From</th>
                                <th class="text-left p-3">To</th>
                                <th class="text-right p-3">Code</th>
                                <th class="text-right p-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-charcoal/5">
                            @foreach ($redirects as $redirect)
                                <tr>
                                    <td class="p-3 font-mono text-xs">{{ $redirect->source_url }}</td>
                                    <td class="p-3 text-xs truncate max-w-[200px]">{{ $redirect->destination_url }}</td>
                                    <td class="p-3 text-right">
                                        <span class="badge {{ $redirect->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $redirect->status_code }}</span>
                                    </td>
                                    <td class="p-3 text-right">
                                        @can('seo.delete')
                                        <x-admin.confirm :action="route('admin.seo.redirects.destroy', $redirect)" title="Remove this redirect?" :message="$redirect->source_url . ' will no longer point to ' . $redirect->destination_url . '.'" label="Remove redirect" />
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            @if ($redirects->isEmpty())
                                <tr><td colspan="4" class="p-6 text-center text-muted text-sm">No redirects configured.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-charcoal/5">{{ $redirects->links() }}</div>
            </div>
        </div>
    </div>
@endsection
