@extends('admin.layout')

@section('title', 'Edit Role — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Role</h1>
            <p class="text-sm text-muted mt-1">{{ $role->title ?? $role->name }}</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @include('admin.roles._form', [
        'role'   => $role,
        'groups' => $groups,
        'action' => route('admin.roles.update', $role),
        'method' => 'PUT',
    ])
@endsection
