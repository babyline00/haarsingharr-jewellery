@extends('admin.layout')

@section('title', 'Active Sessions — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Active Sessions</h1>
            <p class="text-sm text-muted mt-1">Signed-in sessions across the store and back office.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-outline text-sm">Audit Logs</a>
            <a href="{{ route('admin.security-events.index') }}" class="btn-outline text-sm">Security Events</a>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, IP or device…" class="flex-1 min-w-[200px]">
        <button type="submit" class="btn-outline text-sm">Filter</button>
        @if (request('q'))
            <a href="{{ route('admin.active-sessions.index') }}" class="btn-ghost text-sm">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">User</th>
                        <th class="text-left p-3">Device</th>
                        <th class="text-left p-3">IP</th>
                        <th class="text-left p-3">Location</th>
                        <th class="text-right p-3">Last Activity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($sessions as $session)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($session->user)
                                    <a href="{{ route('admin.users.show', $session->user) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $session->user->name }}</a>
                                    <div class="text-xs text-muted">{{ $session->user->email }}</div>
                                @else
                                    <span class="text-muted">Deleted user</span>
                                @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $session->device ?? Str::limit($session->user_agent, 40) ?? '—' }}</td>
                            <td class="p-3 font-mono text-xs text-muted">{{ $session->ip_address }}</td>
                            <td class="p-3 text-charcoal-soft">{{ $session->location ?? '—' }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                {{ $session->last_activity_at?->diffForHumans() }}
                                @if ($session->last_activity_at && $session->last_activity_at->lt(now()->subMinutes($staleMinutes)))
                                    <span class="badge bg-amber-100 text-amber-800 ml-2">Stale</span>
                                @else
                                    <span class="badge bg-emerald-100 text-emerald-800 ml-2">Active</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($sessions->isEmpty())
                        <tr><td colspan="5" class="p-8 text-center text-muted text-sm">No active sessions found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $sessions->links() }}</div>
    </div>
@endsection
