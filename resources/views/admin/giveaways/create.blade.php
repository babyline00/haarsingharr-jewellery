@extends('admin.layout')

@section('title', 'New Giveaway — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">New Giveaway</h1>
            <p class="text-sm text-muted mt-1">Set the prize and coupon rules, then add customers to the draw.</p>
        </div>
        <a href="{{ route('admin.giveaways.index') }}" class="text-sm text-muted hover:text-charcoal">← All giveaways</a>
    </div>

    <div class="card p-5 md:p-6 max-w-3xl">
        @include('admin.giveaways._form')
    </div>
@endsection
