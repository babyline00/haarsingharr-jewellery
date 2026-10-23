@extends('admin.layout')

@section('title', 'Staff Activity Report — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Staff Activity Report</h1>
            <p class="text-sm text-muted mt-1">Audit-log activity by actor and action for the selected period.</p>
        </div>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'staff-activity'])

    <div class="card p-4 md:p-5 mb-6 inline-flex flex-col">
        <div class="text-xs uppercase tracking-widest text-muted">Audit Events</div>
        <div class="font-serif text-2xl md:text-3xl mt-1.5">{{ number_format($total) }}</div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6 mb-6">
        {{-- By actor --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Activity by Actor</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Actor</th>
                            <th class="text-right p-3">Events</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byActor as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3">{{ $row->actor_email ?? 'System' }}</td>
                                <td class="p-3 text-right font-medium">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="p-8 text-center text-muted text-sm">No activity recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- By action --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Activity by Action</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Action</th>
                            <th class="text-right p-3">Events</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($byAction as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3 capitalize">{{ str_replace(['.', '_'], ' ', $row->action) }}</td>
                                <td class="p-3 text-right font-medium">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="p-8 text-center text-muted text-sm">No activity recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent events --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Most Recent Events</h2>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-champagne-dark hover:underline">Full audit trail →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">When</th>
                        <th class="text-left p-3">Actor</th>
                        <th class="text-left p-3">Action</th>
                        <th class="text-left p-3">Module</th>
                        <th class="text-left p-3">IP</th>
                        <th class="text-right p-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($recent as $log)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                            <td class="p-3">{{ $log->actor_email ?? ($log->actor?->email ?? 'System') }}</td>
                            <td class="p-3 capitalize">{{ str_replace(['.', '_'], ' ', $log->action) }}</td>
                            <td class="p-3 capitalize text-charcoal-soft">{{ $log->module ?? '—' }}</td>
                            <td class="p-3 text-xs text-muted font-mono">{{ $log->ip_address ?? '—' }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.audit-logs.show', $log) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No audit events recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
