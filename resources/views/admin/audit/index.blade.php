@extends('admin.layout')

@section('title', 'Audit Logs — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Audit Logs</h1>
            <p class="text-sm text-muted mt-1">Immutable record of every administrative action.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.security-events.index') }}" class="btn-outline text-sm">Security Events</a>
            <a href="{{ route('admin.active-sessions.index') }}" class="btn-outline text-sm">Active Sessions</a>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Action, module, actor, target…" class="flex-1 min-w-[200px]">
        <select name="module">
            <option value="">All modules</option>
            @foreach ($modules as $module)
                <option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ str_replace('_', ' ', $module) }}</option>
            @endforeach
        </select>
        <select name="outcome">
            <option value="">All outcomes</option>
            @foreach (['success', 'failure', 'warning'] as $outcome)
                <option value="{{ $outcome }}" @selected(($filters['outcome'] ?? '') === $outcome)>{{ ucfirst($outcome) }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" title="From">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" title="To">
        <button type="submit" class="btn-outline text-sm">Filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.audit-logs.index') }}" class="btn-ghost text-sm">Clear</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">When</th>
                        <th class="text-left p-3">Actor</th>
                        <th class="text-left p-3">Action</th>
                        <th class="text-left p-3">Module</th>
                        <th class="text-left p-3">Target</th>
                        <th class="text-left p-3">Outcome</th>
                        <th class="text-right p-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($logs as $log)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="p-3">
                                {{ $log->actor_email ?? 'System' }}
                                @if ($log->ip_address) <div class="text-xs text-muted font-mono">{{ $log->ip_address }}</div> @endif
                            </td>
                            <td class="p-3 capitalize">{{ str_replace(['.', '_'], ' ', $log->action) }}</td>
                            <td class="p-3 capitalize text-charcoal-soft">{{ $log->module ? str_replace('_', ' ', $log->module) : '—' }}</td>
                            <td class="p-3 text-charcoal-soft">
                                @if ($log->target_type)
                                    {{ class_basename($log->target_type) }}@if ($log->target_id)<span class="text-muted">#{{ $log->target_id }}</span>@endif
                                @else — @endif
                            </td>
                            <td class="p-3">
                                @php
                                    $outcomeClass = match ($log->outcome) {
                                        'success' => 'bg-emerald-100 text-emerald-800',
                                        'warning' => 'bg-amber-100 text-amber-800',
                                        default   => 'bg-red-100 text-red-800',
                                    };
                                @endphp
                                <span class="badge {{ $outcomeClass }}">{{ ucfirst($log->outcome) }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    @if ($logs->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No audit entries match your filters.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $logs->links() }}</div>
    </div>
@endsection
