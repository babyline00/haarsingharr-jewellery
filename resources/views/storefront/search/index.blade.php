@extends('layouts.storefront')

@section('title', $query ? 'Search: '.$query.' — Haarsingharr' : 'Search — Haarsingharr')

@section('content')
    <section class="section">
        <div class="container-haars">

            {{-- Search header --}}
<<<<<<< HEAD
            <div class="mb-8 md:mb-12 max-w-xl" x-reveal>
=======
            <div class="mb-8 md:mb-12 max-w-xl">
>>>>>>> origin/main
                <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl">Search</h1>
                <form method="get" action="{{ route('search.index') }}" class="mt-4 flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto">
                    <input type="search" name="q" value="{{ $query }}" placeholder="Rings, necklaces, bridal sets…" class="text-sm w-full sm:w-64 lg:w-80">
                    <button type="submit" class="btn-gold shrink-0">Search</button>
                </form>
                @if ($query !== '')
                    <p class="text-sm text-muted mt-3">
                        {{ $products->total() }} {{ \Illuminate\Support\Str::plural('result', $products->total()) }} for “{{ $query }}”.
                    </p>
                @endif
            </div>

            @if ($products->isNotEmpty())
<<<<<<< HEAD
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 xl:gap-8" x-reveal="80">
=======
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 xl:gap-8">
>>>>>>> origin/main
                    @foreach ($products as $product)
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-16 md:py-20">
                    <svg class="w-16 h-16 mx-auto text-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <p class="font-serif text-2xl md:text-3xl text-muted mb-3">
                        @if ($query !== '') No pieces found for “{{ $query }}” @else Search our collection @endif
                    </p>
                    <p class="text-sm text-muted mb-6 max-w-xs mx-auto">Try a different term or explore the full collection.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="{{ route('shop.index') }}" class="btn-gold">Browse All Jewellery</a>
                        <a href="{{ route('shop.bridal') }}" class="btn-outline">Bridal Edit</a>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection