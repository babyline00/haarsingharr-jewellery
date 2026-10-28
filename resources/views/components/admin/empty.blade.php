@props([
    'title' => 'Nothing here yet',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <x-admin.icon :name="$icon" :size="48" class="mx-auto text-muted/40 mb-4" />

    <h3>{{ $title }}</h3>

    @if ($description)
        <p>{{ $description }}</p>
    @endif

    @if (isset($action))
        <div class="mt-4 flex justify-center">{{ $action }}</div>
    @endif
</div>
