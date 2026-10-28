@extends('admin.layout')

@section('title', 'New Staff Member — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Staff Member</h1>
        <a href="{{ route('admin.staff.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    @include('admin.staff._form', [
        'member' => null,
        'roles'  => $roles,
        'action' => route('admin.staff.store'),
        'method' => 'POST',
    ])
@endsection
