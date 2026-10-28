@extends('admin.layout')

@section('title', 'Roles & Permissions — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Roles &amp; Permissions</h1>
            <p class="text-sm text-muted mt-1">Control what each member of staff can access.</p>
        </div>
<<<<<<< HEAD
        <div class="flex items-center gap-2">
            <form method="get" class="flex items-end gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search roles…" class="w-48">
                <button type="submit" class="btn-outline text-sm">Search</button>
            </form>
            @can('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="btn-gold">New Role</a>
            @endcan
        </div>
    </div>

    @can('roles.view')
    <a href="{{ route('admin.roles.matrix') }}" class="btn-outline text-sm mb-4 inline-flex">Open permission matrix</a>
    @endcan

=======
        @can('roles.create')
        <a href="{{ route('admin.roles.create') }}" class="btn-gold">New Role</a>
        @endcan
    </div>

>>>>>>> origin/main
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Role</th>
                        <th class="text-left p-3">Slug</th>
                        <th class="text-right p-3">Users</th>
                        <th class="text-right p-3">Permissions</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
<<<<<<< HEAD
                                @if ($role->slug === 'super-admin' && ! auth()->user()->isSuperAdmin())
                                    <span class="font-medium">{{ $role->title ?? $role->name }}</span>
                                @else
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $role->title ?? $role->name }}</a>
                                @endif
=======
                                <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $role->title ?? $role->name }}</a>
>>>>>>> origin/main
                                @if ($role->description) <div class="text-xs text-muted">{{ Str::limit($role->description, 70) }}</div> @endif
                            </td>
                            <td class="p-3 font-mono text-xs text-charcoal-soft">{{ $role->slug }}</td>
                            <td class="p-3 text-right">{{ $role->users_count }}</td>
                            <td class="p-3 text-right">{{ $role->permissions_count }}</td>
                            <td class="p-3">
                                <span class="badge {{ $role->is_system ? 'bg-slate-100 text-slate-700' : 'bg-champagne/20 text-champagne-dark' }}">{{ $role->is_system ? 'System' : 'Custom' }}</span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
<<<<<<< HEAD
                                @if ($role->slug === 'super-admin' && ! auth()->user()->isSuperAdmin())
                                    <span class="text-xs text-muted">Protected</span>
                                @else
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @endif
                                @can('roles.create')
                                    @if ($role->can_clone)
                                    <form action="{{ route('admin.roles.clone', $role) }}" method="post" class="inline">
                                        @csrf
                                        <button class="text-xs text-charcoal-soft hover:underline ml-2" title="Duplicate this role">Clone</button>
                                    </form>
                                    @endif
                                @endcan
                                @if (! $role->is_system)
                                    @can('roles.delete')
                                    <x-admin.confirm :action="route('admin.roles.destroy', $role)" title="Delete this role?" :message="'The ' . $role->name . ' role and its assignments will be removed.'" label="Delete role" />
=======
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @if (! $role->is_system)
                                    @can('roles.delete')
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="post" class="inline" onsubmit="return confirm('Delete this role?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                    </form>
>>>>>>> origin/main
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $roles->links() }}</div>
    </div>
@endsection
