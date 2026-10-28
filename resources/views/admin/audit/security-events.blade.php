@extends('admin.layout')

@section('title', 'Security Events — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Security Events</h1>
            <p class="text-sm text-muted mt-1">Authentication attempts, failed logins and suspicious activity.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.security-events.export', request()->only(['q', 'event_type', 'outcome', 'from', 'to'])) }}" class="btn-ghost text-sm">Export CSV</a>
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-outline text-sm">Audit Logs</a>
            <a href="{{ route('admin.active-sessions.index') }}" class="btn-outline text-sm">Active Sessions</a>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Email, IP, description…" class="flex-1 min-w-[200px]">
        <select name="event_type">
            <option value="">All event types</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>{{ str_replace(['.', '_'], ' ', $type) }}</option>
            @endforeach
        </select>
        <select name="outcome">
            <option value="">All outcomes</option>
            @foreach (['success', 'failure', 'warning', 'blocked'] as $outcome)
                <option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ ucfirst($outcome) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" title="From">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" title="To">
        <button type="submit" class="btn-outline text-sm">Filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.security-events.index') }}" class="btn-ghost text-sm">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">When</th>
                        <th class="text-left p-3">Event</th>
                        <th class="text-left p-3">Account</th>
                        <th class="text-left p-3">IP</th>
                        <th class="text-left p-3">Description</th>
                        <th class="text-left p-3">Outcome</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($events as $event)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $event->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="p-3 capitalize">{{ str_replace(['.', '_'], ' ', $event->event_type) }}</td>
                            <td class="p-3">
                                @if ($event->user)
                                    <a href="{{ route('admin.users.show', $event->user) }}" class="hover:text-champagne-dark transition-colors">{{ $event->email }}</a>
                                @else
                                    {{ $event->email ?? '—' }}
                                @endif
                            </td>
                            <td class="p-3 font-mono text-xs text-muted">{{ $event->ip_address ?? '—' }}</td>
                            <td class="p-3 text-charcoal-soft">{{ Str::limit($event->description, 70) }}</td>
                            <td class="p-3">
                                @php
                                    $outcomeClass = match ($event->outcome) {
                                        'success' => 'bg-emerald-100 text-emerald-800',
                                        'warning' => 'bg-amber-100 text-amber-800',
                                        'blocked' => 'bg-red-100 text-red-800',
                                        default   => 'bg-orange-100 text-orange-800',
                                    };
                                @endphp
                                <span class="badge {{ $outcomeClass }}">{{ ucfirst($event->outcome) }}</span>
                            </td>
                        </tr>
                    @endforeach
                    @if ($events->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No security events match your filters.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $events->links() }}</div>
    </div>
@endsection
