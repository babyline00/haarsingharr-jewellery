@php
    $role = $role ?? null;
    $assigned = old('permissions', $role?->permissions->pluck('id')->all() ?? []);
@endphp

<form action="{{ $action }}" method="post">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Permissions</h2>
            <div class="space-y-5">
                @foreach ($groups as $module => $permissions)
                    <div>
                        <div class="text-xs uppercase tracking-widest text-muted mb-2">{{ str_replace('_', ' ', $module) }}</div>
                        <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-2">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assigned))>
                                    {{ $permission->action }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="title">Display name *</label>
                    <input type="text" id="title" name="title" required value="{{ old('title', $role->title ?? '') }}" autofocus>
                    @error('title') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="slug">Slug *</label>
                    <input type="text" id="slug" name="slug" required value="{{ old('slug', $role->slug ?? '') }}" placeholder="store-admin" @readonly($role?->is_system)>
                    @error('slug') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3">{{ old('description', $role->description ?? '') }}</textarea>
                </div>
                <div>
                    <label for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $role->sort_order ?? 0) }}">
                </div>
                @if ($role?->is_system)
                    <p class="text-xs text-muted">This is a system role — its slug cannot be changed and it cannot be deleted.</p>
                @endif
            </div>

            <button type="submit" class="btn-gold w-full">{{ $role ? 'Save Role' : 'Create Role' }}</button>
        </aside>
    </div>
</form>
