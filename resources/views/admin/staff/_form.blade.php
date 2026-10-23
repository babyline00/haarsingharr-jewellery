@php
    $member = $member ?? null;
    $assigned = old('roles', $member?->roles->pluck('id')->all() ?? []);
@endphp

<form action="{{ $action }}" method="post">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Account</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name">First name *</label>
                        <input type="text" id="first_name" name="first_name" required value="{{ old('first_name', $member?->first_name) }}" autofocus>
                        @error('first_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="last_name">Last name *</label>
                        <input type="text" id="last_name" name="last_name" required value="{{ old('last_name', $member?->last_name) }}">
                        @error('last_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="{{ old('email', $member?->email) }}">
                        @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $member?->phone) }}">
                    </div>
                    <div>
                        <label for="password">Password {{ $member ? '' : '*' }}</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" @required(! $member)>
                        @if ($member) <p class="text-xs text-muted mt-1">Leave blank to keep the current password.</p> @endif
                        @error('password') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" @required(! $member)>
                    </div>
                    <div>
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            <option value="staff" @selected(old('type', $member?->type->value ?? 'staff') === 'staff')>Staff Member</option>
                            <option value="admin" @selected(old('type', $member?->type->value) === 'admin')>Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            @foreach (['active', 'suspended', 'invited', 'deactivated'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $member?->status->value ?? 'active') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Roles</h2>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $assigned))>
                            {{ $role->title ?? $role->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn-gold w-full">{{ $member ? 'Save Changes' : 'Create Staff Member' }}</button>
        </aside>
    </div>
</form>
