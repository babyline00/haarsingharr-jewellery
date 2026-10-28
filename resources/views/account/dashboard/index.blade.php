@extends('layouts.storefront')

@section('title', 'My Account — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-4xl">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl">Hello, {{ auth()->user()->first_name }}</h1>
                    <p class="text-sm text-muted mt-1">{{ auth()->user()->email }}</p>
                </div>
                <form action="{{ route('logout') }}" method="post">
                    @csrf
                    <button type="submit" class="btn-ghost text-sm">Sign out</button>
                </form>
            </div>

            <div class="grid md:grid-cols-4 gap-4 mb-8">
                <a href="{{ route('account.orders.index') }}" class="card p-5 hover:shadow-md transition-shadow">
                    <div class="text-xs uppercase tracking-widest text-muted">Orders</div>
                    <div class="font-serif text-2xl mt-1">{{ auth()->user()->orders()->count() }}</div>
                </a>
                <a href="{{ route('account.wishlist.index') }}" class="card p-5 hover:shadow-md transition-shadow">
                    <div class="text-xs uppercase tracking-widest text-muted">Wishlist</div>
                    <div class="font-serif text-2xl mt-1">{{ auth()->user()->wishlist?->items->count() ?? 0 }}</div>
                </a>
                <a href="{{ route('account.addresses.index') }}" class="card p-5 hover:shadow-md transition-shadow">
                    <div class="text-xs uppercase tracking-widest text-muted">Addresses</div>
                    <div class="font-serif text-2xl mt-1">{{ auth()->user()->addresses()->count() }}</div>
                </a>
                <a href="{{ route('account.notifications.index') }}" class="card p-5 hover:shadow-md transition-shadow">
                    <div class="text-xs uppercase tracking-widest text-muted">Notifications</div>
                    <div class="font-serif text-2xl mt-1">{{ auth()->user()->unreadNotifications()->count() }}</div>
                </a>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-serif text-xl">Recent Orders</h2>
                    <a href="{{ route('account.orders.index') }}" class="text-xs text-champagne-dark hover:underline">View all →</a>
                </div>
                @php $recent = auth()->user()->orders()->latest()->limit(5)->get(); @endphp
                @if ($recent->isNotEmpty())
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($recent as $order)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <a href="{{ route('account.orders.show', $order->number) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $order->number }}</a>
                                    <div class="text-xs text-muted">{{ $order->created_at->format('j M Y') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium">Rs. {{ number_format($order->grand_total) }}</div>
                                    <span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-muted text-center py-6">No orders yet. <a href="{{ route('shop.index') }}" class="text-champagne-dark hover:underline">Browse the collection →</a></p>
                @endif
            </div>
        </div>
    </section>
@endsection
