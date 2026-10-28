@extends('admin.layout')

@section('title', 'System Health — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">System Health</h1>
            <p class="text-sm text-muted mt-1">Server health, backups, migrations, queue and maintenance control.</p>
        </div>
        <a href="{{ route('admin.database.info') }}" class="btn-ghost text-sm">Database details</a>
    </div>

    {{-- Health grid --}}
    <div class="card overflow-hidden mb-6">
        <div class="p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Health checks</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-charcoal/5">
            @foreach ($health as $check)
                <div class="bg-white p-4" data-testid="health-{{ preg_replace('/[^a-z]+/', '-', strtolower($check['label'])) }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs uppercase tracking-widest text-muted">{{ $check['label'] }}</span>
                        @if ($check['ok'] === true)
                            <x-admin.icon name="check-circle" :size="16" class="text-success" />
                        @elseif ($check['ok'] === false)
                            <x-admin.icon name="alert-triangle" :size="16" class="text-danger" />
                        @endif
                    </div>
                    <p class="font-medium mt-1 break-words">{{ $check['value'] }}</p>
                    @if (! empty($check['note']))
                        <p class="text-xs text-muted mt-0.5">{{ $check['note'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Backups --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5 flex items-center justify-between">
                <h2 class="font-serif text-lg">Backups</h2>
                @can('settings.update')
                    <form action="{{ route('admin.system.backup.create') }}" method="post">
                        @csrf
                        <button type="submit" class="btn-outline text-sm">Create backup now</button>
                    </form>
                @endcan
            </div>

            @if (! $zipAvailable)
                <div class="p-5 bg-amber-50 text-amber-900 text-sm border-b border-amber-200">
                    The PHP <span class="font-mono text-xs">ext-zip</span> extension is missing — backup
                    creation is disabled (download/delete still work). Enable it on the host or create
                    backups via cPanel.
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">File</th>
                            <th class="text-right p-3">Size</th>
                            <th class="text-right p-3">Created</th>
                            <th class="text-right p-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($backups as $backup)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3 font-mono text-xs break-all">{{ $backup['name'] }}</td>
                                <td class="p-3 text-right tabular-nums">{{ $backup['size'] }}</td>
                                <td class="p-3 text-right whitespace-nowrap">{{ $backup['date'] }}</td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.system.backup.download', $backup['name']) }}" class="text-xs text-champagne-dark hover:underline">Download</a>
                                    @can('settings.update')
                                        <x-admin.confirm :action="route('admin.system.backup.destroy', $backup['name'])" title="Delete this backup?" :message="$backup['name'] . ' will be permanently removed.'" label="Delete backup" />
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-muted text-sm">No backups yet. Scheduled: daily 02:30 (after cleanup at 02:15).</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-charcoal/5 text-xs text-muted">
                Restoring a backup is a manual runbook: unzip the archive, import the database dump
                with phpMyAdmin (or cPanel backup restore), then verify the site and check this page.
            </div>
        </div>

        {{-- Maintenance --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Maintenance mode</h2>
            </div>
            <div class="p-5">
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-3 h-3 rounded-full {{ $down ? 'bg-danger' : 'bg-success' }} animate-pulse"></span>
                    <span class="font-medium">{{ $down ? 'Site is currently in maintenance mode' : 'Site is live' }}</span>
                </div>
                @can('settings.update')
                    <form action="{{ route('admin.system.maintenance') }}" method="post" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <label class="text-xs uppercase tracking-widest text-muted">
                            <span class="block mb-1">Type MAINTENANCE to confirm</span>
                            <input type="text" name="confirm" required autocomplete="off">
                        </label>
                        <button type="submit" class="btn-{{ $down ? 'gold' : 'outline' }} text-sm">
                            {{ $down ? 'Bring site back up' : 'Enable maintenance' }}
                        </button>
                    </form>
                    <p class="text-xs text-muted mt-3">
                        While down, shop traffic sees a 503 page. Admin bypass requires the secret from
                        the success flash — keep it for your facing address.
                    </p>
                @endcan
            </div>
        </div>
    </div>

    {{-- Queue failed jobs --}}
    <div class="card overflow-hidden mt-6">
        <div class="p-5 border-b border-charcoal/5 flex items-center justify-between">
            <h2 class="font-serif text-lg">Failed queue jobs</h2>
            @can('settings.update')
                @if (count($failedJobs) > 0)
                    <x-admin.confirm :action="route('admin.system.failed-jobs.flush')" method="POST" title="Flush failed jobs?" message="All failed job records will be removed. This does not retry their work." label="Flush all" confirm-label="Flush jobs" variant="warning" />
                @endif
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">ID</th>
                        <th class="text-left p-3">Queue</th>
                        <th class="text-left p-3">Job</th>
                        <th class="text-left p-3">Failed at</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($failedJobs as $job)
                        @php
                            $payload = json_decode($job->payload, true);
                            $displayName = $payload['displayName'] ?? 'unknown job';
                        @endphp
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs">{{ $job->id }}</td>
                            <td class="p-3">{{ $job->queue }}</td>
                            <td class="p-3 font-mono text-xs max-w-64 truncate" title="{{ Str::limit($job->exception, 200) }}">{{ $displayName }}</td>
                            <td class="p-3 whitespace-nowrap">{{ $job->failed_at }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                @can('settings.update')
                                    <form action="{{ route('admin.system.failed-jobs.retry', $job->id) }}" method="post" class="inline">
                                        @csrf
                                        <button class="text-xs text-champagne-dark hover:underline">Retry</button>
                                    </form>
                                    <x-admin.confirm :action="route('admin.system.failed-jobs.delete', $job->id)" method="POST" title="Delete this failed job?" message="This failed job record will be permanently removed without retrying it." label="Delete failed job" />
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-muted text-sm">No failed jobs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Migrations & schedule --}}
    <div class="grid lg:grid-cols-2 gap-6 mt-6">
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Migrations</h2>
            </div>
            <pre class="p-4 text-xs overflow-x-auto max-h-72 bg-ivory-alt/20">{{ $migrations }}</pre>
        </div>
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Schedule</h2>
            </div>
            <pre class="p-4 text-xs overflow-x-auto max-h-72 bg-ivory-alt/20">{{ $schedule }}</pre>
        </div>
    </div>
@endsection
