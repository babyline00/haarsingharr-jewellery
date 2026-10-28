@php
    $g = $giveaway ?? null;
    $editing = (bool) $g;
@endphp

<form action="{{ $editing ? route('admin.giveaways.update', $g) : route('admin.giveaways.store') }}" method="post">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <div class="grid md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label for="name">Giveaway name</label>
            <input id="name" name="name" value="{{ old('name', $g->name ?? '') }}" required maxlength="160" placeholder="Eid Gift Draw">
            @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="3" maxlength="2000" placeholder="Who can enter, when the draw happens, etc.">{{ old('description', $g->description ?? '') }}</textarea>
            @error('description') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label for="prize">Prize label</label>
            <input id="prize" name="prize" value="{{ old('prize', $g->prize ?? '') }}" required maxlength="160" placeholder="10% OFF — Eid Gift">
            <p class="mt-1 text-xs text-muted">Shown to the winner and in their notification.</p>
            @error('prize') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="coupon_type">Coupon type</label>
            <select id="coupon_type" name="coupon_type" required>
                @foreach (['percentage' => 'Percentage off', 'fixed' => 'Fixed amount off', 'free_shipping' => 'Free shipping'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('coupon_type', $g->coupon_type ?? 'percentage') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('coupon_type') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="coupon_value">Coupon value</label>
            <input id="coupon_value" name="coupon_value" type="number" step="0.01" min="0" value="{{ old('coupon_value', $g->coupon_value ?? 10) }}" required>
            <p class="mt-1 text-xs text-muted">Percentage or rupee amount. Ignored for free shipping.</p>
            @error('coupon_value') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="coupon_min_order">Minimum order value</label>
            <input id="coupon_min_order" name="coupon_min_order" type="number" step="0.01" min="0" value="{{ old('coupon_min_order', $g->coupon_min_order ?? 0) }}">
            @error('coupon_min_order') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="coupon_expiry_days">Coupon expiry (days)</label>
            <input id="coupon_expiry_days" name="coupon_expiry_days" type="number" min="1" max="365" value="{{ old('coupon_expiry_days', $g->coupon_expiry_days ?? 30) }}" required>
            @error('coupon_expiry_days') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="winners_count">Number of winners</label>
            <input id="winners_count" name="winners_count" type="number" min="1" max="100" value="{{ old('winners_count', $g->winners_count ?? 1) }}" required>
            @error('winners_count') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach (['draft' => 'Draft', 'open' => 'Open for entries', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $g->status ?? 'open') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="draw_at">Scheduled draw (optional)</label>
            <input id="draw_at" name="draw_at" type="datetime-local" value="{{ old('draw_at', isset($g->draw_at) ? $g->draw_at?->format('Y-m-d\TH:i') : '') }}">
            @error('draw_at') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 mt-6">
        <a href="{{ $editing ? route('admin.giveaways.show', $g) : route('admin.giveaways.index') }}" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-gold">{{ $editing ? 'Save Giveaway' : 'Create Giveaway' }}</button>
    </div>
</form>
