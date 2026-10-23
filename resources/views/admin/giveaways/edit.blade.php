@extends('admin.layout')

@section('title', 'Edit Giveaway — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Giveaway</h1>
            <p class="text-sm text-muted mt-1">{{ $giveaway->name }}</p>
        </div>
        <a href="{{ route('admin.giveaways.show', $giveaway) }}" class="text-sm text-muted hover:text-charcoal">← Back to giveaway</a>
    </div>

    @if ($giveaway->isDrawn())
        <div class="alert alert-warning mb-6">
            This giveaway has been drawn. Coupon and winner details cannot be changed.
        </div>
    @endif

    <div class="card p-5 md:p-6 max-w-3xl">
        @include('admin.giveaways._form')
    </div>
@endsection
