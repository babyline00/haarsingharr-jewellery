@extends('admin.layout')

@section('title', 'Permission Matrix — Haarsingharr Admin')

@php
    $locked = $lockedRole ?? null;
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Permission Matrix</h1>
            <p class="text-sm text-muted mt-1">Modules × actions across every role. You can grant only permissions you already hold.</p>
        </div>
        <a href="{{ route('admin.roles.index') }}" class="btn-ghost text-sm">Back to Roles</a>
    </div>

    @if ($locked)
        <div class="alert alert-info mb-6">
                <div class="text-sm"><strong>{{ $locked['title'] }}</strong> is protected — its column is read-only.</div>
        </div>
    @endif

    <form action="{{ route('admin.roles.matrix.update') }}" method="post" class="card overflow-hidden">
        @csrf
        @foreach ($matrixRoles as $column)
            @unless ($column['locked'])
                <input type="hidden" name="matrix_roles[]" value="{{ $column['id'] }}">
            @endunless
        @endforeach
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3 min-w-56">Permission</th>
                        @foreach ($matrixRoles as $column)
                            <th class="p-3 text-center whitespace-nowrap" title="{{ $column['slug'] }}">
                                <span class="block font-medium text-charcoal">{{ $column['title'] }}</span>
                                @if ($column['locked'])
                                    <span class="text-[10px] text-muted font-normal lowercase">{{ $column['owned'] ? '(you)' : '(protected)' }}</span>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($modules as $module => $modulePermissions)
                        <tr class="bg-ivory-alt/20">
                            <td colspan="{{ count($matrixRoles) + 1 }}" class="p-2">
                                <span class="text-xs uppercase tracking-widest font-medium">{{ str_replace('-', ' ', $module) }}</span>
                            </td>
                        </tr>
                        @foreach ($modulePermissions as $permission)
                            <tr class="hover:bg-ivory-alt/20 transition-colors">
                                <td class="p-2 ps-4 font-mono text-xs text-charcoal-soft">{{ $permission->action }}</td>
                                @foreach ($matrixRoles as $column)
                                    <td class="p-2 text-center">
                                        <input type="checkbox"
                                               name="matrix[{{ $column['id'] }}][{{ $permission->id }}]" value="{{ $permission->id }}"
                                               class="mx-auto h-4 w-4 accent-champagne"
                                               @checked(in_array($permission->id, $column['permissions']))
                                                @disabled($column['locked'] || ! in_array($permission->id, $manageablePermissionIds, true))>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-charcoal/5 flex justify-end">
            <button type="submit" class="btn-gold">Save Matrix</button>
        </div>
    </form>
@endsection
