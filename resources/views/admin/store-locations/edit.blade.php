@extends('admin.layout')

@section('title', 'Edit Store Location — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Location</h1>
            <p class="text-sm text-muted mt-1">{{ $location->name }}</p>
        </div>
        <a href="{{ route('admin.store-locations.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @include('admin.store-locations._form', [
        'location' => $location,
        'action'   => route('admin.store-locations.update', $location),
        'method'   => 'PUT',
    ])
@endsection
