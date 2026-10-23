@extends('admin.layout')

@section('title', 'Database Info — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Database Information</h1>
            <p class="text-sm text-muted mt-1">Real-time database connection status and table overview.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost text-sm">Back to Dashboard</a>
    </div>

    {{-- Storage Link Check --}}
    @if (! file_exists(public_path('storage')))
        <div class="alert alert-warning mb-6">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <p class="font-medium">Storage symlink missing</p>
                <p class="text-sm mt-1">The <code>public/storage</code> symlink does not exist. Uploaded images will not display. Run <code>php artisan storage:link</code> or create the symlink manually.</p>
            </div>
        </div>
    @endif

    {{-- Connection Status --}}
    <div class="card p-6 mb-6">
        <h2 class="font-serif text-lg mb-4">Connection Status</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="flex items-center gap-3 p-3 bg-ivory-alt/30 rounded-lg">
                <div class="w-3 h-3 rounded-full bg-success animate-pulse"></div>
                <div>
                    <div class="text-xs text-muted uppercase tracking-widest">Status</div>
                    <div class="font-medium text-success">Connected</div>
                </div>
            </div>
            <div class="p-3 bg-ivory-alt/30 rounded-lg">
                <div class="text-xs text-muted uppercase tracking-widest">Driver</div>
                <div class="font-medium">{{ ucfirst($connection) }}</div>
            </div>
            <div class="p-3 bg-ivory-alt/30 rounded-lg">
                <div class="text-xs text-muted uppercase tracking-widest">Host</div>
                <div class="font-medium">{{ $host }}:{{ $port }}</div>
            </div>
            <div class="p-3 bg-ivory-alt/30 rounded-lg">
                <div class="text-xs text-muted uppercase tracking-widest">Database</div>
                <div class="font-medium">{{ $database }}</div>
            </div>
            <div class="p-3 bg-ivory-alt/30 rounded-lg">
                <div class="text-xs text-muted uppercase tracking-widest">Username</div>
                <div class="font-medium">{{ $username }}</div>
            </div>
            <div class="p-3 bg-ivory-alt/30 rounded-lg">
                <div class="text-xs text-muted uppercase tracking-widest">Total Tables</div>
                <div class="font-medium">{{ $totalTables }}</div>
            </div>
        </div>
    </div>

    {{-- Table Overview --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Database Tables</h2>
            <div class="text-sm text-muted">{{ $totalTables }} tables · {{ number_format($totalRows) }} total rows</div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">#</th>
                        <th class="text-left p-3">Table Name</th>
                        <th class="text-right p-3">Rows</th>
                        <th class="text-right p-3">Size</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($tables as $i => $table)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-muted">{{ $i + 1 }}</td>
                            <td class="p-3 font-mono text-sm">{{ $table['name'] }}</td>
                            <td class="p-3 text-right font-medium">{{ number_format($table['rows']) }}</td>
                            <td class="p-3 text-right text-muted text-xs">—</td>
                        </tr>
                    @endforeach
                    @if (empty($tables))
                        <tr><td colspan="4" class="p-8 text-center text-muted">No tables found.</td></tr>
                    @endif
                </tbody>
                <tfoot class="bg-ivory-alt/30">
                    <tr>
                        <td colspan="2" class="p-3 font-medium">Total</td>
                        <td class="p-3 text-right font-medium">{{ number_format($totalRows) }}</td>
                        <td class="p-3 text-right text-muted text-xs">—</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="card p-5 mt-6">
        <h2 class="font-serif text-lg mb-4">Quick Actions</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.settings.index') }}" class="btn-outline text-sm">Store Settings</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-outline text-sm">Audit Logs</a>
            <a href="{{ route('admin.reports.sales') }}" class="btn-outline text-sm">Reports</a>
        </div>
    </div>
@endsection
