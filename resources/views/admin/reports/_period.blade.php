{{-- Period / export filter shared by report screens.
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
