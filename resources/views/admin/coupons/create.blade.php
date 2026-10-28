@extends('admin.layout')

@section('title', 'New Coupon — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Coupon</h1>
        <a href="{{ route('admin.coupons.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    <form action="{{ route('admin.coupons.store') }}" method="post">
        @csrf
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Discount</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="code">Code *</label>
                            <input type="text" id="code" name="code" required value="{{ old('code') }}" class="font-mono uppercase" autofocus>
                            @error('code') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="type">Type *</label>
                            <select id="type" name="type">
                                @foreach (['percentage' => 'Percentage', 'fixed' => 'Fixed amount', 'free_shipping' => 'Free shipping', 'product_specific' => 'Product specific', 'category_specific' => 'Category specific'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="value">Value *</label>
                            <input type="number" step="0.01" min="0" id="value" name="value" required value="{{ old('value') }}">
                            <p class="text-xs text-muted mt-1">Percent for percentage type, otherwise amount in Rs.</p>
                        </div>
                        <div>
                            <label for="max_discount_amount">Max discount (Rs.)</label>
                            <input type="number" step="0.01" min="0" id="max_discount_amount" name="max_discount_amount" value="{{ old('max_discount_amount') }}">
                        </div>
                        <div>
                            <label for="min_order_value">Minimum order value (Rs.)</label>
                            <input type="number" step="0.01" min="0" id="min_order_value" name="min_order_value" value="{{ old('min_order_value') }}">
                        </div>
                        <div>
                            <label for="status">Status *</label>
                            <select id="status" name="status">
                                @foreach (['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'disabled' => 'Disabled'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <h3 class="font-serif text-base">Usage</h3>
                    <div>
                        <label for="usage_limit_total">Total usage limit</label>
                        <input type="number" min="1" id="usage_limit_total" name="usage_limit_total" value="{{ old('usage_limit_total') }}">
                    </div>
                    <div>
                        <label for="usage_limit_per_customer">Per-customer limit</label>
                        <input type="number" min="1" id="usage_limit_per_customer" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer') }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="first_order_only" value="1" @checked(old('first_order_only'))>
                        First order only
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="exclude_sale_items" value="1" @checked(old('exclude_sale_items'))>
                        Exclude sale items
                    </label>
                </div>

                <div class="card p-5 space-y-4">
                    <h3 class="font-serif text-base">Schedule</h3>
                    <div>
                        <label for="starts_at">Starts at</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at') }}">
                    </div>
                    <div>
                        <label for="expires_at">Expires at</label>
                        <input type="datetime-local" id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full">Create Coupon</button>
            </aside>
        </div>
    </form>
@endsection
