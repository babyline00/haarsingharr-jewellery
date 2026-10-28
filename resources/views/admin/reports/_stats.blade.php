{{-- Summary stat cards with optional compare deltas.
     Expects: $stats = [{label, value, key?}], $deltas = {key: {text, trend}} (optional). --}}
<div class="grid grid-cols-2 {{ (count($stats) > 3 ? ('md:grid-cols-'.min(count($stats), 5)) : 'md:grid-cols-'.max(count($stats), 1)).' ' }} gap-4 mb-6">
    @foreach ($stats as $stat)
        @php $delta = ($deltas ?? [])[$stat['key'] ?? $stat['label']] ?? null; @endphp
        <x-admin.stat
            :label="$stat['label']"
            :value="$stat['value']"
            :delta="$delta['text'] ?? null"
            :trend="$delta['trend'] ?? 'flat'"
        />
    @endforeach
</div>
