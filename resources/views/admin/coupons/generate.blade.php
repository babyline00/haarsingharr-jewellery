@extends('admin.layout')

@section('title', 'Generate Coupon Codes — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Bulk generate coupon codes"
                    subtitle="Create a batch of unique one-time codes — a code pool for influencers, campaigns or loyalty gifts.">
        <x-slot:actions>
            <a href="{{ route('admin.coupons.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    <form action="{{ route('admin.coupons.generate.store') }}" method="post">
        @csrf

        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <x-admin.card>
                    <h2 class="font-serif text-lg mb-4">Naming &amp; quantity</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <x-admin.field name="prefix" label="Prefix" hint="Letters/numbers only — codes look like PREFIX-ABCDE.">
                            <x-admin.input id="prefix-generate" name="prefix" required
                                           :value="old('prefix', 'HAPPY')" class="font-mono uppercase" />
                        </x-admin.field>

                        <x-admin.field name="quantity" label="How many codes">
                            <x-admin.input id="quantity-generate" name="quantity" type="number" min="1" max="100" required
                                           :value="old('quantity', 10)" />
                        </x-admin.field>

                        <x-admin.field name="code_length" label="Random part length" hint="4–12 characters.">
                            <x-admin.input id="code-length" name="code_length" type="number" min="4" max="12"
                                           :value="old('code_length', 5)" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <h2 class="font-serif text-lg mb-4">Discount</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-admin.field name="type" label="Type *">
                            <x-admin.select id="type-generate" name="type" :selected="old('type', 'fixed')"
                                            :options="['percentage' => 'Percentage', 'fixed' => 'Fixed amount', 'free_shipping' => 'Free shipping']" />
                        </x-admin.field>

                        <x-admin.field name="value" label="Value *">
                            <x-admin.input id="value-generate" name="value" type="number" step="0.01" min="0" required :value="old('value', 500)" />
                        </x-admin.field>

                        <x-admin.field name="min_order_value" label="Minimum order value (Rs.)">
                            <x-admin.input id="min-order-generate" name="min_order_value" type="number" step="0.01" min="0" :value="old('min_order_value')" />
                        </x-admin.field>

                        <x-admin.field name="max_discount_amount" label="Max discount (Rs.)">
                            <x-admin.input id="max-discount-generate" name="max_discount_amount" type="number" step="0.01" min="0" :value="old('max_discount_amount')" />
                        </x-admin.field>
                    </div>
                </x-admin.card>
            </div>

            <aside class="space-y-4">
                <x-admin.card>
                    <h2 class="font-serif text-base mb-4">Usage limits</h2>
                    <div class="space-y-4">
                        <x-admin.field name="usage_limit_total" label="Per code total limit">
                            <x-admin.input id="limit-total-generate" name="usage_limit_total" type="number" min="1" :value="old('usage_limit_total', 1)" />
                        </x-admin.field>

                        <x-admin.field name="usage_limit_per_customer" label="Per-customer limit">
                            <x-admin.input id="limit-customer-generate" name="usage_limit_per_customer" type="number" min="1" :value="old('usage_limit_per_customer', 1)" />
                        </x-admin.field>

                        <x-admin.checkbox name="first_order_only" label="First order only" :checked="old('first_order_only', true)" />
                        <x-admin.checkbox name="exclude_sale_items" label="Exclude sale items" :checked="old('exclude_sale_items', false)" />
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <h2 class="font-serif text-base mb-4">Schedule &amp; status</h2>
                    <div class="space-y-4">
                        <x-admin.field name="starts_at" label="Starts at">
                            <x-admin.input id="starts-at-generate" name="starts_at" type="datetime-local" :value="old('starts_at')" />
                        </x-admin.field>

                        <x-admin.field name="expires_at" label="Expires at">
                            <x-admin.input id="expires-at-generate" name="expires_at" type="datetime-local" :value="old('expires_at')" />
                        </x-admin.field>

                        <x-admin.field name="status" label="Status *">
                            <x-admin.select id="status-generate" name="status" :selected="old('status', 'active')"
                                            :options="['active' => 'Active', 'scheduled' => 'Scheduled', 'disabled' => 'Disabled']" />
                        </x-admin.field>
                    </div>

                    <x-admin.button type="submit" variant="gold" class="w-full mt-5" icon="save">Generate codes</x-admin.button>
                </x-admin.card>
            </aside>
        </div>
    </form>
@endsection
