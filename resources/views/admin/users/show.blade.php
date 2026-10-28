@extends('admin.layout')

@section('title', ($user->name ?: $user->email) . ' — Haarsingharr Admin')

@php
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'suspended' => 'bg-amber-100 text-amber-800',
        'invited' => 'bg-blue-100 text-blue-800',
        'deactivated' => 'bg-rose-100 text-rose-800',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">{{ $user->name ?: 'User' }}</h1>
            <p class="text-sm text-muted mt-1">{{ $user->email }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $statusColors[$user->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $user->status->label() }}</span>
            <a href="{{ route('admin.users.edit', $user) }}" class="btn-outline text-sm">Edit</a>
            <a href="{{ route('admin.users.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card p-5"><div class="text-xs uppercase tracking-widest text-muted">Orders</div><div class="font-serif text-2xl mt-1">{{ $user->orders_count }}</div></div>
        <div class="card p-5"><div class="text-xs uppercase tracking-widest text-muted">Reviews</div><div class="font-serif text-2xl mt-1">{{ $user->reviews_count }}</div></div>
        <div class="card p-5"><div class="text-xs uppercase tracking-widest text-muted">Addresses</div><div class="font-serif text-2xl mt-1">{{ $user->addresses->count() }}</div></div>
        <div class="card p-5"><div class="text-xs uppercase tracking-widest text-muted">Roles</div><div class="font-serif text-2xl mt-1">{{ $user->roles->count() }}</div></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5 text-sm space-y-2">
            <h2 class="font-serif text-lg mb-2">Profile</h2>
            <div class="flex justify-between"><span class="text-muted">Type</span><span>{{ $user->type->label() }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Phone</span><span>{{ $user->phone ? $user->phone_country.' '.$user->phone : '—' }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Email verified</span><span>{{ $user->email_verified_at?->format('M j, Y') ?? 'No' }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Marketing consent</span><span>{{ $user->marketing_consent ? 'Yes' : 'No' }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Last login</span><span>{{ $user->last_login_at?->format('M j, Y H:i') ?? 'Never' }}</span></div>
            <div class="flex justify-between"><span class="text-muted">Joined</span><span>{{ $user->created_at?->format('M j, Y') }}</span></div>
            @if ($user->internal_notes)
                <div class="pt-2 border-t border-charcoal/5">
                    <div class="text-muted mb-1">Internal notes</div>
                    <p class="whitespace-pre-line">{{ $user->internal_notes }}</p>
                </div>
            @endif
        </div>

        <div class="card p-5">
            <h2 class="font-serif text-lg mb-3">Roles</h2>
            @forelse ($user->roles as $role)
                <span class="badge bg-ivory-alt text-charcoal-soft">{{ $role->title ?? $role->name }}</span>
            @empty
                <p class="text-sm text-muted">No roles assigned.</p>
            @endforelse
        </div>
    </div>
@endsection
