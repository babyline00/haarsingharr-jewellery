@extends('admin.layout')

@section('title', 'Roles & Permissions — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Roles &amp; Permissions</h1>
            <p class="text-sm text-muted mt-1">Control what each member of staff can access.</p>
        </div>
        @can('roles.create')
        <a href="{{ route('admin.roles.create') }}" class="btn-gold">New Role</a>
        @endcan
    </div>

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
                                <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $role->title ?? $role->name }}</a>
                                @if ($role->description) <div class="text-xs text-muted">{{ Str::limit($role->description, 70) }}</div> @endif
                            </td>
                            <td class="p-3 font-mono text-xs text-charcoal-soft">{{ $role->slug }}</td>
                            <td class="p-3 text-right">{{ $role->users_count }}</td>
                            <td class="p-3 text-right">{{ $role->permissions_count }}</td>
                            <td class="p-3">
                                <span class="badge {{ $role->is_system ? 'bg-slate-100 text-slate-700' : 'bg-champagne/20 text-champagne-dark' }}">{{ $role->is_system ? 'System' : 'Custom' }}</span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @if (! $role->is_system)
                                    @can('roles.delete')
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="post" class="inline" onsubmit="return confirm('Delete this role?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-danger hover:underline ml-2">Delete</button>
                                    </form>
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
