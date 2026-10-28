@extends('admin.layout')

@section('title', 'New Role — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Role</h1>
        <a href="{{ route('admin.roles.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    @include('admin.roles._form', [
        'role'   => null,
        'groups' => $groups,
        'action' => route('admin.roles.store'),
        'method' => 'POST',
    ])
@endsection
