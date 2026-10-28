@extends('admin.layout')

@section('title', 'Edit Homepage Section — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Section</h1>
            <p class="text-sm text-muted mt-1 font-mono">{{ $section->key }}</p>
        </div>
        <a href="{{ route('admin.homepage-sections.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @include('admin.homepage._form', [
        'section' => $section,
        'action'  => route('admin.homepage-sections.update', $section),
        'method'  => 'PUT',
    ])
@endsection
