@extends('admin.layout')

@section('title', 'New Homepage Section — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Homepage Section</h1>
        <a href="{{ route('admin.homepage-sections.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    @include('admin.homepage._form', [
        'section' => null,
        'action'  => route('admin.homepage-sections.store'),
        'method'  => 'POST',
    ])
@endsection
