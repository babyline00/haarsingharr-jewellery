@extends('admin.layout')

@section('title', 'New Banner — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Banner</h1>
        <a href="{{ route('admin.banners.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    @include('admin.banners._form', [
        'banner' => null,
        'action' => route('admin.banners.store'),
        'method' => 'POST',
    ])
@endsection
