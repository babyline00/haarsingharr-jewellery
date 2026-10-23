@extends('admin.layout')

@section('title', 'Edit Staff Member — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Staff Member</h1>
            <p class="text-sm text-muted mt-1">{{ $member->email }}</p>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @include('admin.staff._form', [
        'member' => $member,
        'roles'  => $roles,
        'action' => route('admin.staff.update', $member),
        'method' => 'PUT',
    ])
@endsection
