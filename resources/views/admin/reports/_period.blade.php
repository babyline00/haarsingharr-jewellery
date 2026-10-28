{{-- Period / export filter shared by report screens.
<<<<<<< HEAD
     Expects: $from, $to, $type (report slug).
     Optional: $compare (bool), $compareFrom, $compareTo. --}}
<div class="flex flex-wrap items-end gap-3 mb-4">
    {{-- Filter + compare (GET) --}}
    <form method="get" class="flex flex-wrap items-end gap-3" novalidate>
        <label class="text-xs uppercase tracking-widest text-muted">
            <span class="block mb-1">From</span>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}">
        </label>
        <label class="text-xs uppercase tracking-widest text-muted">
            <span class="block mb-1">To</span>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}">
        </label>
        @if ($hasCompare ?? true)
            <label class="flex items-center gap-2 text-xs text-muted pb-2">
                <input type="checkbox" name="compare" value="1" class="h-4 w-4 rounded border-charcoal/20 accent-champagne"
                       @checked($compare ?? false)>
                Compare with previous period
            </label>
        @endif
        <button type="submit" class="btn-outline text-sm">Apply</button>
    </form>

    {{-- Exports + email (no GET wrapper) --}}
    @can('reports.export')
    <div class="flex items-center gap-2 ml-auto">
        <a href="{{ route('admin.reports.export', ['type' => $type,
            'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
           class="btn-ghost text-sm">CSV</a>
        <a href="{{ route('admin.reports.export', ['type' => $type, 'format' => 'xlsx',
            'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
           class="btn-ghost text-sm">Excel</a>
        <form method="post" action="{{ route('admin.reports.email', $type) }}" class="inline-flex">
            @csrf
            <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
            <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
            <button type="submit" class="btn-outline text-sm">Email me</button>
        </form>
    </div>
    @endcan

    <p class="text-xs text-muted w-full">
        {{ $from->format('M j, Y') }} &rarr; {{ $to->format('M j, Y') }}
        @if ($compare ?? false)
            &nbsp;&middot;&nbsp;compared with {{ $compareFrom->format('M j, Y') }} &rarr; {{ $compareTo->format('M j, Y') }}
        @endif
    </p>
</div>
=======
     Expects: $from, $to, $type (report slug). --}}
<form method="get" class="flex flex-wrap items-end gap-3 mb-6">
    <label class="text-xs uppercase tracking-widest text-muted">
        <span class="block mb-1">From</span>
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}">
    </label>
    <label class="text-xs uppercase tracking-widest text-muted">
        <span class="block mb-1">To</span>
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}">
    </label>
    <button type="submit" class="btn-outline text-sm">Apply</button>
    <a href="{{ route('admin.reports.export', ['type' => $type, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
       class="btn-ghost text-sm">Export CSV</a>
    <span class="text-xs text-muted ml-auto">{{ $from->format('M j, Y') }} &rarr; {{ $to->format('M j, Y') }}</span>
</form>
>>>>>>> origin/main
