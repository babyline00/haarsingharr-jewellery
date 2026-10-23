@php $location = $location ?? null; @endphp

<form action="{{ $action }}" method="post">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Location</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required value="{{ old('name', $location?->name) }}" autofocus>
                        @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="address">Address *</label>
                        <textarea id="address" name="address" rows="3" required>{{ old('address', $location?->address) }}</textarea>
                        @error('address') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $location?->phone) }}">
                        </div>
                        <div>
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $location?->whatsapp) }}">
                        </div>
                        <div>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $location?->email) }}">
                            @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="hours">Opening hours</label>
                            <input type="text" id="hours" name="hours" value="{{ old('hours', $location?->hours) }}" placeholder="Mon–Sat, 10:00–20:00">
                        </div>
                    </div>
                    <div>
                        <label for="map_link">Map link</label>
                        <input type="text" id="map_link" name="map_link" value="{{ old('map_link', $location?->map_link) }}" placeholder="https://maps.google.com/...">
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="{{ old('slug', $location?->slug) }}" placeholder="auto-generated">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="latitude">Latitude</label>
                        <input type="text" id="latitude" name="latitude" value="{{ old('latitude', $location?->latitude) }}">
                    </div>
                    <div>
                        <label for="longitude">Longitude</label>
                        <input type="text" id="longitude" name="longitude" value="{{ old('longitude', $location?->longitude) }}">
                    </div>
                </div>
                <div>
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" @selected(old('status', $location?->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $location?->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div>
                    <label for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $location?->sort_order ?? 0) }}">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_main" value="1" @checked(old('is_main', $location?->is_main))>
                    Primary / flagship location
                </label>
            </div>

            <button type="submit" class="btn-gold w-full">{{ $location ? 'Save Changes' : 'Create Location' }}</button>
        </aside>
    </div>
</form>
