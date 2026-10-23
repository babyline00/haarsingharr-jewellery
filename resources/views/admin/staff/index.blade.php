@extends('admin.layout')

@section('title', 'Staff — Haarsingharr Admin')

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
            <h1 class="font-serif text-2xl md:text-3xl">Staff</h1>
            <p class="text-sm text-muted mt-1">Team members with access to the admin panel.</p>
        </div>
        @can('staff.create')
        <a href="{{ route('admin.staff.create') }}" class="btn-gold">New Staff Member</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or email" class="flex-1 min-w-[200px]">
        <select name="role">
            <option value="">All roles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->title ?? $role->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Name</th>
                        <th class="text-left p-3">Roles</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Last login</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($staff as $member)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.staff.edit', $member) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $member->name ?: '—' }}</a>
                                <div class="text-xs text-muted">{{ $member->email }}</div>
                            </td>
                            <td class="p-3">
                                @forelse ($member->roles as $role)
                                    <span class="badge bg-ivory-alt text-charcoal-soft">{{ $role->title ?? $role->name }}</span>
                                @empty
                                    <span class="text-xs text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $member->type->label() }}</td>
                            <td class="p-3"><span class="badge {{ $statusColors[$member->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $member->status->label() }}</span></td>
                            <td class="p-3 text-xs text-muted">{{ $member->last_login_at?->format('M j, Y') ?? 'Never' }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.staff.edit', $member) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('staff.delete')
                                @if ($member->id !== auth()->id())
                                <form action="{{ route('admin.staff.destroy', $member) }}" method="post" class="inline" onsubmit="return confirm('Remove this staff member?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                </form>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($staff->isEmpty())
                        <tr><td colspan="6" class="p-8 text-center text-muted text-sm">No staff members found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $staff->links() }}</div>
    </div>
@endsection
