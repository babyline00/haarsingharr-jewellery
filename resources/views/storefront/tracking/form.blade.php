@extends('layouts.storefront')

@section('title', 'Track Your Order — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-md">
            <h1 class="font-serif text-3xl md:text-4xl mb-6">Track Your Order</h1>
            <p class="text-sm text-muted mb-8">Enter your order number and the email address used to place the order.</p>

            <div class="card p-6 md:p-8">
                <form action="{{ route('order.tracking.lookup') }}" method="post">
                    @csrf
                    <div class="mb-4">
                        <label for="order_number">Order number</label>
                        <input type="text" id="order_number" name="order_number" required value="{{ old('order_number') }}" placeholder="AU-25-000001">
                        @error('order_number') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="email">Email used to place order</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}">
                        @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-gold w-full">Track Order</button>
                </form>
            </div>

            @if (session('error'))
                <div class="mt-4 bg-danger/10 border border-danger/30 text-danger px-4 py-3 rounded-md text-sm">{{ session('error') }}</div>
            @endif
        </div>
    </section>
@endsection
