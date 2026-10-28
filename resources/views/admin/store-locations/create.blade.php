@extends('admin.layout')

@section('title', 'New Store Location — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Store Location</h1>
        <a href="{{ route('admin.store-locations.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    @include('admin.store-locations._form', [
        'location' => null,
        'action'   => route('admin.store-locations.store'),
        'method'   => 'POST',
    ])
@endsection
