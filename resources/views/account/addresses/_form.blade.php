@php $address = $address ?? null; @endphp

<form action="{{ $action }}" method="post" class="mt-4 space-y-3">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid md:grid-cols-2 gap-3">
        <div>
            <label for="label-{{ $address?->id }}">Label</label>
            <input type="text" id="label-{{ $address?->id }}" name="label" value="{{ old('label', $address?->label) }}" placeholder="Home, Office…">
        </div>
        <div>
            <label for="phone_country-{{ $address?->id }}">Country code *</label>
            <input type="text" id="phone_country-{{ $address?->id }}" name="phone_country" value="{{ old('phone_country', $address?->phone_country ?? '+92') }}" required>
        </div>
        <div>
            <label for="first_name-{{ $address?->id }}">First name *</label>
            <input type="text" id="first_name-{{ $address?->id }}" name="first_name" value="{{ old('first_name', $address?->first_name) }}" required>
        </div>
        <div>
            <label for="last_name-{{ $address?->id }}">Last name *</label>
            <input type="text" id="last_name-{{ $address?->id }}" name="last_name" value="{{ old('last_name', $address?->last_name) }}" required>
        </div>
        <div>
            <label for="phone-{{ $address?->id }}">Phone *</label>
            <input type="text" id="phone-{{ $address?->id }}" name="phone" value="{{ old('phone', $address?->phone) }}" required>
        </div>
        <div>
            <label for="city-{{ $address?->id }}">City *</label>
            <input type="text" id="city-{{ $address?->id }}" name="city" value="{{ old('city', $address?->city) }}" required>
        </div>
    </div>

    <div>
        <label for="address_line_1-{{ $address?->id }}">Address line 1 *</label>
        <input type="text" id="address_line_1-{{ $address?->id }}" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" required>
    </div>
    <div>
        <label for="address_line_2-{{ $address?->id }}">Address line 2</label>
        <input type="text" id="address_line_2-{{ $address?->id }}" name="address_line_2" value="{{ old('address_line_2', $address?->address_line_2) }}">
    </div>

    <div class="grid md:grid-cols-3 gap-3">
        <div>
            <label for="landmark-{{ $address?->id }}">Landmark</label>
            <input type="text" id="landmark-{{ $address?->id }}" name="landmark" value="{{ old('landmark', $address?->landmark) }}">
        </div>
        <div>
            <label for="province-{{ $address?->id }}">Province *</label>
            <input type="text" id="province-{{ $address?->id }}" name="province" value="{{ old('province', $address?->province) }}" required>
        </div>
        <div>
            <label for="postal_code-{{ $address?->id }}">Postal code</label>
            <input type="text" id="postal_code-{{ $address?->id }}" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}">
        </div>
    </div>

    <div>
        <label for="country-{{ $address?->id }}">Country *</label>
        <input type="text" id="country-{{ $address?->id }}" name="country" maxlength="2" value="{{ old('country', $address?->country ?? 'PK') }}" required>
    </div>

    <div class="flex flex-wrap gap-4 text-sm">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_default_shipping" value="1" @checked(old('is_default_shipping', $address?->is_default_shipping))>
            Default shipping
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_default_billing" value="1" @checked(old('is_default_billing', $address?->is_default_billing))>
            Default billing
        </label>
    </div>

    <button type="submit" class="btn-gold">{{ $submit ?? 'Save' }}</button>
</form>
