@extends('admin.layout')

@section('title', 'Search — Haarsingharr Admin')

@section('content')
    <div class="mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Search</h1>
        <p class="text-sm text-muted mt-1">Orders, customers and products — press <kbd class="px-1.5 py-0.5 rounded border border-charcoal/10 bg-porcelain text-xs">⌘K</kbd> anywhere to come back here.</p>
    </div>

    <form action="{{ route('admin.search') }}" method="get" class="card p-4 mb-6">
        <label for="admin-search-q" class="sr-only">Search admin</label>
        <div class="flex gap-2">
            <input type="search" id="admin-search-q" name="q" value="{{ $q }}" autofocus
                   placeholder="Order number, customer email, product name or SKU…" class="flex-1">
            <button type="submit" class="btn-gold">Search</button>
        </div>
    </form>

    @if (mb_strlen($q) < 2)
        <div class="card p-10 text-center">
            <p class="font-serif text-lg">Start typing to search</p>
            <p class="text-sm text-muted mt-1">Enter at least two characters.</p>
        </div>
    @elseif ($total === 0)
        <div class="card p-10 text-center">
            <p class="font-serif text-lg">No matches for “{{ $q }}”</p>
            <p class="text-sm text-muted mt-1">Try a different order number, email or product name.</p>
        </div>
    @else
        <p class="text-sm text-muted mb-4">{{ $total }} result{{ $total === 1 ? '' : 's' }} for “{{ $q }}”</p>

        <div class="space-y-6">
            @if ($orders->isNotEmpty())
                <section class="card overflow-hidden">
                    <header class="px-5 py-3 border-b border-charcoal/5 flex items-center justify-between">
                        <h2 class="font-serif text-lg">Orders</h2>
                        <span class="badge bg-ivory-alt text-charcoal-soft">{{ $orders->count() }}</span>
                    </header>
                    <ul class="divide-y divide-charcoal/5">
                        @foreach ($orders as $order)
                            <li>
                                <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-ivory-alt/40 transition-colors">
                                    <span class="font-medium text-sm">{{ $order->number }}</span>
                                    <span class="text-sm text-muted">Rs. {{ number_format($order->grand_total) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($customers->isNotEmpty())
                <section class="card overflow-hidden">
                    <header class="px-5 py-3 border-b border-charcoal/5 flex items-center justify-between">
                        <h2 class="font-serif text-lg">Customers</h2>
                        <span class="badge bg-ivory-alt text-charcoal-soft">{{ $customers->count() }}</span>
                    </header>
                    <ul class="divide-y divide-charcoal/5">
                        @foreach ($customers as $customer)
                            <li>
                                <a href="{{ route('admin.customers.show', $customer) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-ivory-alt/40 transition-colors">
                                    <span class="text-sm">
                                        <span class="font-medium">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                                        <span class="text-muted"> · {{ $customer->email }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($products->isNotEmpty())
                <section class="card overflow-hidden">
                    <header class="px-5 py-3 border-b border-charcoal/5 flex items-center justify-between">
                        <h2 class="font-serif text-lg">Products</h2>
                        <span class="badge bg-ivory-alt text-charcoal-soft">{{ $products->count() }}</span>
                    </header>
                    <ul class="divide-y divide-charcoal/5">
                        @foreach ($products as $product)
                            <li>
                                <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center justify-between gap-4 px-5 py-3 hover:bg-ivory-alt/40 transition-colors">
                                    <span class="text-sm">
                                        <span class="font-medium">{{ $product->name }}</span>
                                        @if ($product->sku)<span class="text-muted"> · {{ $product->sku }}</span>@endif
                                    </span>
                                    <span class="text-sm text-muted">Rs. {{ number_format((float) $product->currentPrice()) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    @endif
@endsection
