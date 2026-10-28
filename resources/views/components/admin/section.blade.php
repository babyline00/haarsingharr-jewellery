@props([
    'title',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'py-5 border-b border-charcoal/5 last:border-0 first:pt-0']) }}>
    <div class="mb-4">
        <h2 class="font-serif text-lg leading-tight">{{ $title }}</h2>
        @if ($description)
            <p class="text-sm text-muted mt-0.5">{{ $description }}</p>
        @endif
    </div>

    <div class="space-y-4">
        {{ $slot }}
    </div>
</section>
