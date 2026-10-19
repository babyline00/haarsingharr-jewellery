@extends('layouts.storefront')

@section('title', 'Checkout — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars">
            <h1 class="font-serif text-3xl md:text-4xl mb-8">Checkout</h1>

            <div class="grid lg:grid-cols-[1fr_400px] gap-8 lg:gap-12">

                <form action="{{ route('checkout.store') }}" method="post" id="checkout-form" x-data="{ sameBilling: true }">
                    @csrf

                    {{-- Customer --}}
                    <fieldset class="mb-8">
                        <legend class="font-serif text-xl mb-4">Contact Details</legend>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="email">Email address *</label>
                                <input type="email" id="email" name="email" required value="{{ old('email', $user?->email) }}" autocomplete="email">
                                @error('email') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="phone">Phone *</label>
                                <div class="flex gap-2">
                                    <select name="phone_country" class="w-24 text-sm">
                                        <option value="+92" @selected(in_array(old('phone_country', '+92'), ['+92']))>+92</option>
                                        <option value="+1">+1</option>
                                        <option value="+44">+44</option>
                                        <option value="+971">+971</option>
                                        <option value="+966">+966</option>
                                        <option value="+91">+91</option>
                                    </select>
                                    <input type="tel" id="phone" name="phone" required value="{{ old('phone', $user?->phone) }}" placeholder="300 1234567" autocomplete="tel">
                                </div>
                                @error('phone') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Shipping address --}}
                    <fieldset class="mb-8">
                        <legend class="font-serif text-xl mb-4">Shipping Address</legend>

                        @if ($user && $user->addresses->isNotEmpty())
                            <div class="mb-4 space-y-2">
                                @foreach ($user->addresses as $address)
                                    <label class="block p-4 border border-charcoal/10 rounded-md cursor-pointer hover:border-champagne transition-colors">
                                        <input type="radio" name="address_id" value="{{ $address->id }}" class="hidden peer" onchange="fetch('{{ route('account.addresses.fill', $address->id) }}').then(r => r.json()).then(d => fillShipping(d))">
                                        <div class="peer-checked:bg-ivory-alt/30 -m-4 p-4 rounded-md">
                                            <div class="font-medium text-sm">{{ $address->fullName() }}</div>
                                            <div class="text-xs text-muted mt-1">{{ $address->fullAddress() }}</div>
                                            <div class="text-xs text-muted">{{ $address->fullPhone() }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="shipping_first_name">First name *</label>
                                <input type="text" id="shipping_first_name" name="shipping_first_name" required value="{{ old('shipping_first_name', $user?->first_name) }}" autocomplete="given-name">
                                @error('shipping_first_name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shipping_last_name">Last name *</label>
                                <input type="text" id="shipping_last_name" name="shipping_last_name" required value="{{ old('shipping_last_name', $user?->last_name) }}" autocomplete="family-name">
                                @error('shipping_last_name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="shipping_address_1">Address *</label>
                                <input type="text" id="shipping_address_1" name="shipping_address_1" required value="{{ old('shipping_address_1') }}" autocomplete="address-line1" placeholder="House #, Street, Block">
                                @error('shipping_address_1') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="shipping_address_2">Apartment, suite (optional)</label>
                                <input type="text" id="shipping_address_2" name="shipping_address_2" value="{{ old('shipping_address_2') }}" autocomplete="address-line2">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="shipping_landmark">Landmark (optional)</label>
                                <input type="text" id="shipping_landmark" name="shipping_landmark" value="{{ old('shipping_landmark') }}" placeholder="Near...">
                            </div>
                            <div>
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required value="{{ old('shipping_city') }}" autocomplete="address-level2">
                                @error('shipping_city') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shipping_province">Province *</label>
                                <select id="shipping_province" name="shipping_province" required>
                                    <option value="">Select province…</option>
                                    @foreach (['punjab' => 'Punjab', 'sindh' => 'Sindh', 'kpk' => 'Khyber Pakhtunkhwa', 'balochistan' => 'Balochistan', 'islamabad' => 'Islamabad Capital', 'gilgit' => 'Gilgit-Baltistan', 'ajk' => 'Azad Jammu & Kashmir'] as $key => $label)
                                        <option value="{{ $key }}" @selected(old('shipping_province') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('shipping_province') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="shipping_postal_code">Postal code</label>
                                <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}" autocomplete="postal-code">
                            </div>
                            <div>
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country">
                                    <option value="PK" selected>Pakistan</option>
                                </select>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 mt-4 text-sm">
                            <input type="checkbox" x-model="sameBilling" :checked="sameBilling">
                            Billing address is the same as shipping
                        </label>

                        <div x-show="!sameBilling" x-cloak class="mt-4 grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="billing_first_name">Billing first name</label>
                                <input type="text" id="billing_first_name" name="billing_first_name" value="{{ old('billing_first_name') }}">
                            </div>
                            <div>
                                <label for="billing_last_name">Billing last name</label>
                                <input type="text" id="billing_last_name" name="billing_last_name" value="{{ old('billing_last_name') }}">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="billing_address_1">Billing address</label>
                                <input type="text" id="billing_address_1" name="billing_address_1" value="{{ old('billing_address_1') }}">
                            </div>
                            <div>
                                <label for="billing_city">Billing city</label>
                                <input type="text" id="billing_city" name="billing_city" value="{{ old('billing_city') }}">
                            </div>
                            <div>
                                <label for="billing_province">Billing province</label>
                                <select id="billing_province" name="billing_province">
                                    <option value="">Select…</option>
                                    @foreach (['punjab', 'sindh', 'kpk', 'balochistan', 'islamabad', 'gilgit', 'ajk'] as $p)
                                        <option value="{{ $p }}">{{ ucfirst($p) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Shipping method --}}
                    <fieldset class="mb-8">
                        <legend class="font-serif text-xl mb-4">Shipping Method</legend>
                        <div class="space-y-2">
                            <label class="block p-4 border border-charcoal/10 rounded-md cursor-pointer peer-checked:bg-ivory-alt/30 hover:border-champagne">
                                <input type="radio" name="shipping_method" value="standard" @checked(old('shipping_method', 'standard') === 'standard') required class="peer hidden">
                                <div class="peer-checked:border-champagne flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">Standard delivery</div>
                                        <div class="text-xs text-muted">2–4 business days, TCS / Leopards</div>
                                    </div>
                                    <div class="font-serif text-base">Free</div>
                                </div>
                            </label>
                            <label class="block p-4 border border-charcoal/10 rounded-md cursor-pointer hover:border-champagne">
                                <input type="radio" name="shipping_method" value="express" @checked(old('shipping_method') === 'express') class="peer hidden">
                                <div class="peer-checked:border-champagne flex items-center justify-between">
                                    <div>
                                        <div class="font-medium">Express delivery</div>
                                        <div class="text-xs text-muted">Next business day, before 6pm</div>
                                    </div>
                                    <div class="font-serif text-base">Rs. {{ number_format(config('storefront.shipping.express_rate', 700)) }}</div>
                                </div>
                            </label>
                        </div>
                        @error('shipping_method') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Payment method --}}
                    <fieldset class="mb-8">
                        <legend class="font-serif text-xl mb-4">Payment Method</legend>
                        <div class="space-y-2">
                            @foreach ($gateways as $code => $gateway)
                                <label class="block p-4 border border-charcoal/10 rounded-md cursor-pointer hover:border-champagne">
                                    <input type="radio" name="payment_method" value="{{ $code }}" @checked(old('payment_method', 'cod') === $code) required class="peer hidden">
                                    <div class="peer-checked:border-champagne flex items-center gap-3">
                                        <div>
                                            <div class="font-medium">{{ $gateway->label() }}</div>
                                            @if ($code === 'cod')
                                                <div class="text-xs text-muted">Pay cash on delivery. ID verification required.</div>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </fieldset>

                    {{-- Notes & consent --}}
                    <fieldset class="mb-8">
                        <label for="notes">Order notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Special delivery instructions, gift message, etc.">{{ old('notes') }}</textarea>

                        <label class="flex items-start gap-2 mt-4 text-sm">
                            <input type="checkbox" name="agree_terms" required value="1" @checked(old('agree_terms'))>
                            <span class="text-charcoal-soft">
                                I agree to the <a href="{{ route('pages.show', 'terms') }}" target="_blank" class="underline hover:text-champagne">Terms</a> and
                                <a href="{{ route('pages.show', 'exchange-policy') }}" target="_blank" class="underline hover:text-champagne">Exchange & Refund Policy</a>.
                            </span>
                        </label>
                        @error('agree_terms') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </fieldset>

                    <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">

                    <button type="submit" class="btn-gold w-full text-base py-3" id="place-order-btn">
                        Place Order — Rs. {{ number_format($cart->total) }}
                    </button>
                </form>

                {{-- Summary --}}
                <aside class="lg:sticky lg:top-24 self-start">
                    <div class="card p-6">
                        <h2 class="font-serif text-xl mb-4">Order Summary</h2>
                        <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                            @foreach ($cart->items as $item)
                                <div class="flex gap-3">
                                    <div class="w-16 h-16 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                        @if ($item->product?->primaryImageCardPath())
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->product->primaryImageCardPath()) }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>
                                    <div class="flex-1 text-sm">
                                        <div class="font-medium leading-tight">{{ $item->product->name }}</div>
                                        @if ($item->productVariant?->formattedAttributes())
                                            <div class="text-xs text-muted">{{ $item->productVariant->formattedAttributes() }}</div>
                                        @endif
                                        <div class="text-xs text-muted">Qty: {{ $item->quantity }}</div>
                                    </div>
                                    <div class="text-sm font-medium">Rs. {{ number_format($item->line_total) }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-charcoal/10 mt-4 pt-4 space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>Rs. {{ number_format($cart->subtotal) }}</span></div>
                            @if ($cart->discount > 0)
                                <div class="flex justify-between text-success"><span class="text-muted">Discount</span><span>-Rs. {{ number_format($cart->discount) }}</span></div>
                            @endif
                            <div class="flex justify-between"><span class="text-muted">Shipping</span><span>{{ $cart->shipping > 0 ? 'Rs. ' . number_format($cart->shipping) : 'Free' }}</span></div>
                            <div class="border-t border-charcoal/10 pt-3 mt-3 flex justify-between items-baseline">
                                <span class="font-serif text-lg">Total</span>
                                <span class="font-serif text-2xl">Rs. {{ number_format($cart->total) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-muted text-center">
                        Prices include all taxes. Your card/bank details are processed securely by the payment provider — Haarsingharr never stores them.
                    </div>
                </aside>
            </div>
        </div>
    </section>

    @push('scripts')
        <script>
            // Prevent double submission (client-side layer; server-side idempotency is the actual guard).
            const form = document.getElementById('checkout-form');
            const button = document.getElementById('place-order-btn');
            form.addEventListener('submit', () => {
                button.disabled = true;
                button.textContent = 'Placing your order...';
            });

            function fillShipping(data) {
                ['first_name', 'last_name', 'address_1', 'address_2', 'landmark', 'city', 'province', 'postal_code'].forEach(field => {
                    const el = document.getElementById('shipping_' + field);
                    if (el && data[field]) {
                        el.value = data[field];
                        if (el.tagName === 'SELECT') el.dispatchEvent(new Event('change'));
                    }
                });
            }
        </script>
    @endpush
@endsection
