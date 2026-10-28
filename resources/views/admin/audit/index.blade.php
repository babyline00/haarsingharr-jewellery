@extends('admin.layout')

@section('title', 'Audit Logs — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Audit Logs" subtitle="Immutable record of every administrative action.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.audit-logs.export', request()->only(['q', 'module', 'action', 'outcome', 'from', 'to']))" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
            <x-admin.button :href="route('admin.security-events.index')" variant="outline" size="sm" icon="shield">
                Security Events
            </x-admin.button>
            <x-admin.button :href="route('admin.active-sessions.index')" variant="outline" size="sm" icon="clock">
                Active Sessions
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <label for="audit-search" class="sr-only">Search audit logs</label>
            <x-admin.input id="audit-search" name="q" type="search" :value="$filters['q'] ?? ''"
                           placeholder="Action, module, actor, target…" />
        </div>

        <div class="w-full sm:w-44">
            <label for="audit-module" class="sr-only">Module</label>
            <x-admin.select id="audit-module" name="module" :selected="$filters['module'] ?? ''"
                            placeholder="All modules"
                            :options="collect($modules)->mapWithKeys(fn ($module) => [$module => ucfirst(str_replace('_', ' ', $module))])->all()" />
        </div>

        <div class="w-full sm:w-40">
            <label for="audit-outcome" class="sr-only">Outcome</label>
            <x-admin.select id="audit-outcome" name="outcome" :selected="$filters['outcome'] ?? ''"
                            placeholder="All outcomes"
                            :options="['success' => 'Success', 'failure' => 'Failure', 'warning' => 'Warning']" />
        </div>

        <div class="w-full sm:w-40">
            <label for="audit-from" class="sr-only">From date</label>
            <x-admin.input id="audit-from" name="from" type="date" :value="$filters['from'] ?? ''" />
        </div>

        <div class="w-full sm:w-40">
            <label for="audit-to" class="sr-only">To date</label>
            <x-admin.input id="audit-to" name="to" type="date" :value="$filters['to'] ?? ''" />
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.audit-logs.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Audit log entries">
            <thead>
                <tr>
                    <x-admin.th label="When" />
                    <x-admin.th label="Actor" />
                    <x-admin.th label="Action" />
                    <x-admin.th label="Module" />
                    <x-admin.th label="Target" />
                    <x-admin.th label="Outcome" />
                    <x-admin.th label="Details" align="right" />
                </tr>
            </thead>

            <tbody class="divide-y divide-charcoal/5">
                @forelse ($logs as $log)
                    <tr>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $log->created_at?->format('M j, Y g:i A') }}</td>

                        <td class="p-3">
                            {{ $log->actor_email ?? 'System' }}
                            @if ($log->ip_address)
                                <div class="text-xs text-muted font-mono mt-0.5">{{ $log->ip_address }}</div>
                            @endif
                        </td>

                        <td class="p-3 capitalize">{{ str_replace(['.', '_'], ' ', $log->action) }}</td>
                        <td class="p-3 capitalize text-charcoal-soft">{{ $log->module ? str_replace('_', ' ', $log->module) : '—' }}</td>

                        <td class="p-3 text-charcoal-soft">
                            @if ($log->target_type)
                                {{ class_basename($log->target_type) }}@if ($log->target_id)<span class="text-muted">#{{ $log->target_id }}</span>@endif
                            @else
                                —
                            @endif
                        </td>

                        <td class="p-3">
                            <x-admin.status-pill :status="$log->outcome" />
                        </td>

                        <td class="p-3 text-right">
                            <a href="{{ route('admin.audit-logs.show', $log) }}"
                               class="text-xs text-champagne-dark hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="list"
                                           title="No audit entries"
                                           description="No entries match these filters. Try widening the date range or clearing the search." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($logs->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$logs" />
            </div>
        @endif
    </x-admin.card>
=======
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
>>>>>>> origin/main
@endsection
