@extends('admin.layout')

@section('title', 'Edit User — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit User</h1>
            <p class="text-sm text-muted mt-1">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    @php $assigned = old('roles', $user->roles->pluck('id')->all()); @endphp

    <form action="{{ route('admin.users.update', $user) }}" method="post">
        @csrf @method('PUT')
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Account</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name">First name *</label>
                        <input type="text" id="first_name" name="first_name" required value="{{ old('first_name', $user->first_name) }}">
                        @error('first_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="last_name">Last name *</label>
                        <input type="text" id="last_name" name="last_name" required value="{{ old('last_name', $user->last_name) }}">
                        @error('last_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="{{ old('email', $user->email) }}">
                        @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                    </div>
                    <div>
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            @foreach (['customer' => 'Customer', 'staff' => 'Staff Member', 'admin' => 'Administrator'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $user->type->value) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            @foreach (['active', 'suspended', 'invited', 'deactivated'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $user->status->value) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password">
                        <p class="text-xs text-muted mt-1">Leave blank to keep the current password.</p>
                        @error('password') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
<<<<<<< HEAD
                @if ($canManageRoles)
=======
>>>>>>> origin/main
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Roles</h2>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        @foreach ($roles as $role)
<<<<<<< HEAD
                            @php
                                $roleCanBeManaged = auth()->user()->isSuperAdmin()
                                    || ($role->name !== 'super-admin' && $role->permissions->every(fn ($permission) => auth()->user()->can($permission->name)));
                            @endphp
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $assigned)) @disabled(! $roleCanBeManaged)>
=======
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $assigned))>
>>>>>>> origin/main
                                {{ $role->title ?? $role->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
<<<<<<< HEAD
                @endif
=======
>>>>>>> origin/main
                <button type="submit" class="btn-gold w-full">Save Changes</button>
            </aside>
        </div>
    </form>
@endsection
