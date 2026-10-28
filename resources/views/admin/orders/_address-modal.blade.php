@php
    $address = $order->addresses->firstWhere('type', $type);
    $title = $type === 'shipping' ? 'Edit shipping address' : 'Edit billing address';
@endphp

<x-admin.modal name="address-{{ $type }}" :title="$title" max-width="max-w-xl">
    <form method="POST" action="{{ route('admin.orders.addresses.update', [$order, $type]) }}" class="space-y-3">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-3">
            <x-admin.field name="first_name" label="First name" required>
                <x-admin.input name="first_name" :value="$address?->first_name" required />
            </x-admin.field>
            <x-admin.field name="last_name" label="Last name" required>
                <x-admin.input name="last_name" :value="$address?->last_name" required />
            </x-admin.field>
        </div>

        <div class="grid sm:grid-cols-3 gap-3">
            <x-admin.field name="phone_country" label="Country code" required>
                <x-admin.input name="phone_country" :value="$address?->phone_country ?? '+92'" required />
            </x-admin.field>
            <div class="sm:col-span-2">
                <x-admin.field name="phone" label="Phone" required>
                    <x-admin.input name="phone" type="tel" :value="$address?->phone" required />
                </x-admin.field>
            </div>
        </div>

        <x-admin.field name="address_line_1" label="Address line 1" required>
            <x-admin.input name="address_line_1" :value="$address?->address_line_1" required />
        </x-admin.field>

        <x-admin.field name="address_line_2" label="Address line 2">
            <x-admin.input name="address_line_2" :value="$address?->address_line_2" />
        </x-admin.field>

        <x-admin.field name="landmark" label="Landmark">
            <x-admin.input name="landmark" :value="$address?->landmark" />
        </x-admin.field>

        <div class="grid sm:grid-cols-3 gap-3">
            <x-admin.field name="city" label="City" required>
                <x-admin.input name="city" :value="$address?->city" required />
            </x-admin.field>
            <x-admin.field name="province" label="Province / state" required>
                <x-admin.input name="province" :value="$address?->province" required />
            </x-admin.field>
            <x-admin.field name="postal_code" label="Postal code">
                <x-admin.input name="postal_code" :value="$address?->postal_code" />
            </x-admin.field>
        </div>

        <x-admin.field name="country" label="Country (2-letter code)" required>
            <x-admin.input name="country" :value="$address?->country ?? 'PK'" maxlength="2" required />
        </x-admin.field>

        <div class="flex justify-end gap-2 pt-3">
            <x-admin.button type="button" variant="outline" size="sm"
                            x-on:click="$dispatch('close-modal-address-{{ $type }}')">Cancel</x-admin.button>
            <x-admin.button type="submit" size="sm" icon="save">Save address</x-admin.button>
        </div>
    </form>
</x-admin.modal>
