@extends('admin.layout')

@section('title', 'Users — Haarsingharr Admin')

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
            <h1 class="font-serif text-2xl md:text-3xl">Users</h1>
            <p class="text-sm text-muted mt-1">Every account — customers, staff and administrators.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name, email or phone" class="flex-1 min-w-[200px]">
        <select name="type">
            <option value="">All types</option>
            @foreach (['customer', 'staff', 'admin'] as $type)
                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (array_keys($statusColors) as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">User</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Roles</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Joined</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($users as $user)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.users.show', $user) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $user->name ?: '—' }}</a>
                                <div class="text-xs text-muted">{{ $user->email }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $user->type->label() }}</td>
                            <td class="p-3">
                                @forelse ($user->roles as $role)
                                    <span class="badge bg-ivory-alt text-charcoal-soft">{{ $role->title ?? $role->name }}</span>
                                @empty
                                    <span class="text-xs text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="p-3"><span class="badge {{ $statusColors[$user->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $user->status->label() }}</span></td>
                            <td class="p-3 text-xs text-muted">{{ $user->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                                @if (auth()->user()->isSuperAdmin() || (! $user->isSuperAdmin() && $user->type->value !== 'admin'))
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-xs text-champagne-dark hover:underline ml-2">Edit</a>
                                @else
                                    <span class="text-xs text-muted ml-2">Protected</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($users->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No users found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $users->links() }}</div>
    </div>
@endsection
