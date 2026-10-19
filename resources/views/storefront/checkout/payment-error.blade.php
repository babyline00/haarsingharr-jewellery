@extends('layouts.storefront')

@section('title', 'Payment Issue — Haarsingharr')

@section('content')
    <section class="py-20">
        <div class="container-haars max-w-md text-center">
            <div class="w-16 h-16 mx-auto rounded-full bg-danger/10 flex items-center justify-center mb-6">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-danger"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <h1 class="font-serif text-2xl mb-3">Payment Session Expired</h1>
            <p class="text-sm text-muted mb-8">{{ $message ?? 'Your payment session could not be completed.' }}</p>
            <a href="{{ route('account.dashboard') }}" class="btn-gold">Back to Account</a>
        </div>
    </section>
@endsection
