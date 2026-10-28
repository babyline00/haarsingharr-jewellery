@extends('admin.layout')

@section('title', 'Audit Entry — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl capitalize">{{ str_replace(['.', '_'], ' ', $log->action) }}</h1>
            <p class="text-sm text-muted mt-1">
                {{ $log->created_at?->format('l, j F Y \a\t g:i:s A') }}
                &middot; Entry #{{ $log->id }}
            </p>
        </div>
        <a href="{{ route('admin.audit-logs.index') }}" class="btn-outline text-sm">&larr; Back to audit logs</a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Meta --}}
        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Details</h2>
            <dl class="text-sm space-y-3">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Actor</dt>
                    <dd class="text-right">{{ $log->actor_email ?? $log->actor?->email ?? 'System' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Module</dt>
                    <dd class="text-right capitalize">{{ $log->module ? str_replace('_', ' ', $log->module) : '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Action</dt>
                    <dd class="text-right font-mono text-xs">{{ $log->action }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Target</dt>
                    <dd class="text-right">{{ $log->target_type ? class_basename($log->target_type) : '—' }}@if ($log->target_id) <span class="text-muted">#{{ $log->target_id }}</span>@endif</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Outcome</dt>
                    <dd class="text-right">
                        @php
                            $outcomeClass = match ($log->outcome) {
                                'success' => 'bg-emerald-100 text-emerald-800',
                                'warning' => 'bg-amber-100 text-amber-800',
                                default   => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="badge {{ $outcomeClass }}">{{ ucfirst($log->outcome) }}</span>
                    </dd>
                </div>
                @can('audit.view_sensitive')
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">IP Address</dt>
                        <dd class="text-right font-mono text-xs">{{ $log->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">User Agent</dt>
                        <dd class="text-right text-xs break-all">{{ $log->user_agent ?? '—' }}</dd>
                    </div>
                @endcan
            </dl>
            @if ($log->note)
                <p class="text-sm text-charcoal-soft border-t border-charcoal/5 mt-4 pt-4">{{ $log->note }}</p>
            @endif
        </div>

        {{-- Payloads --}}
        <div class="lg:col-span-2 space-y-6">
            @can('audit.view_sensitive')
                @foreach ([['Before', $log->previous], ['After', $log->after], ['Properties', $log->properties]] as [$label, $payload])
                    <div class="card overflow-hidden">
                        <div class="p-4 border-b border-charcoal/5">
                            <h2 class="font-serif text-lg">{{ $label }}</h2>
                        </div>
                        @if (filled($payload))
                            <pre class="p-4 text-xs overflow-x-auto bg-ivory-alt/20">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <p class="p-4 text-sm text-muted">No {{ strtolower($label) }} data recorded.</p>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="card p-5 text-sm text-muted">
                    Detailed before/after payloads require the <span class="font-mono text-xs">audit.view_sensitive</span> permission.
                </div>
            @endcan

            {{-- Related activity (per-record activity tab) --}}
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Related activity</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-xs uppercase tracking-widest text-muted mb-2">Same target</h3>
                        <ul class="divide-y divide-charcoal/5 text-sm">
                            @forelse ($relatedTarget as $entry)
                                <li class="py-2 flex items-center justify-between gap-2">
                                    <a href="{{ route('admin.audit-logs.show', $entry) }}" class="link">{{ $entry->action }}</a>
                                    <span class="text-xs text-muted whitespace-nowrap">{{ $entry->created_at?->diffForHumans() }}</span>
                                </li>
                            @empty
                                <li class="py-2 text-muted">First event for this target.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-xs uppercase tracking-widest text-muted mb-2">Same actor</h3>
                        <ul class="divide-y divide-charcoal/5 text-sm">
                            @forelse ($relatedActor as $entry)
                                <li class="py-2 flex items-center justify-between gap-2">
                                    <a href="{{ route('admin.audit-logs.show', $entry) }}" class="link">{{ $entry->action }}</a>
                                    <span class="text-xs text-muted whitespace-nowrap">{{ $entry->created_at?->diffForHumans() }}</span>
                                </li>
                            @empty
                                <li class="py-2 text-muted">First recorded action by this actor.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
