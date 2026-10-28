@extends('admin.layout')

@section('title', ($customer->name ?: $customer->email) . ' — Haarsingharr Admin')

@php
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'suspended' => 'bg-amber-100 text-amber-800',
        'invited' => 'bg-blue-100 text-blue-800',
        'deactivated' => 'bg-rose-100 text-rose-800',
    ];

    $tabs = collect([
        ['key' => 'orders', 'label' => 'Orders', 'count' => $orderCount],
        ['key' => 'addresses', 'label' => 'Addresses', 'count' => $customer->addresses->count()],
        ['key' => 'reviews', 'label' => 'Reviews', 'count' => $customer->reviews->count()],
        ['key' => 'wishlist', 'label' => 'Wishlist', 'count' => $customer->wishlist?->items->count() ?? 0],
        ['key' => 'notes', 'label' => 'Profile & notes'],
    ])->map(fn ($tab) => [
        'label'  => $tab['label'].(isset($tab['count']) ? ' ('.$tab['count'].')' : ''),
        'url'    => route('admin.customers.show', array_merge([$customer], ['tab' => $tab['key']])),
        'active' => $tab === $tab['key'],
    ])->values()->all();
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">{{ $customer->name ?: 'Customer' }}</h1>
            <p class="text-sm text-muted mt-1">
                {{ $customer->email }}
                @if ($customer->phone) · {{ $customer->phone_country.' '.$customer->phone }} @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $statusColors[$customer->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $customer->status->label() }}</span>
            <a href="{{ route('admin.customers.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <x-admin.stat label="Lifetime value" :value="'Rs. '.number_format($lifetimeSpend, 2)" icon="wallet"
                      :delta="$orderCount.' order(s)'" />
        <x-admin.stat label="Reviews" :value="number_format($customer->reviews->count())" icon="star" />
        <x-admin.stat label="Wishlist items" :value="number_format($customer->wishlist?->items->count() ?? 0)" icon="star" />
        <x-admin.stat label="Marketing consent" :value="$customer->marketing_consent ? 'Opted in' : 'No'" icon="mail" />
    </div>

    <x-admin.tabs :items="$tabs" />

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            @if ($tab === 'orders')
                <div class="card overflow-hidden">
                    <div class="p-5 pb-3 flex items-center justify-between">
                        <h2 class="font-serif text-lg">All orders</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                                <tr>
                                    <th class="text-left p-3">Number</th>
                                    <th class="text-left p-3">Status</th>
                                    <th class="text-right p-3">Total</th>
                                    <th class="text-left p-3">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-charcoal/5">
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="p-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-champagne-dark hover:underline">{{ $order->number }}</a></td>
                                        <td class="p-3"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                                        <td class="p-3 text-right">{{ number_format($order->grand_total, 2) }}</td>
                                        <td class="p-3 text-xs text-muted">{{ $order->created_at?->format('M j, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="p-6 text-center text-muted text-sm">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t border-charcoal/5">{{ $orders->links() }}</div>
                </div>
            @elseif($tab === 'addresses')
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Addresses</h2>
                    @if ($customer->addresses->isNotEmpty())
                        <div class="grid md:grid-cols-2 gap-4">
                            @foreach ($customer->addresses as $address)
                                <div class="border border-charcoal/10 rounded-md p-3 text-sm">
                                    <div class="font-medium">{{ $address->label ?: 'Address' }}
                                        @if ($address->is_default_shipping) <span class="badge bg-ivory-alt text-charcoal-soft">Default shipping</span> @endif
                                        @if ($address->is_default_billing) <span class="badge bg-ivory-alt text-charcoal-soft">Default billing</span> @endif
                                    </div>
                                    <div class="text-charcoal-soft mt-1">{{ $address->fullName() }}</div>
                                    <div class="text-muted text-xs mt-1">{{ $address->fullAddress() }}</div>
                                    <div class="text-muted text-xs">{{ $address->fullPhone() }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted">No addresses on file.</p>
                    @endif
                </div>
            @elseif($tab === 'reviews')
                <div class="card overflow-hidden">
                    <div class="p-5 pb-3"><h2 class="font-serif text-lg">Reviews</h2></div>
                    <div class="p-3 space-y-3">
                        @forelse ($reviews as $review)
                            <div class="border border-charcoal/10 rounded-md p-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}</span>
                                    <span class="badge {{ $review->is_approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $review->is_approved ? 'Published' : 'Pending' }}</span>
                                </div>
                                @if ($review->title) <div class="font-medium mt-1">{{ $review->title }}</div> @endif
                                <p class="text-charcoal-soft mt-1 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($review->body, 200) }}</p>
                                <div class="text-xs text-muted mt-2">
                                    {{ $review->product?->name ?? 'Product removed' }} · {{ $review->created_at?->format('M j, Y') }}
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted text-sm p-6">No reviews written.</p>
                        @endforelse
                    </div>
                    <div class="p-3 border-t border-charcoal/5">{{ $reviews->links() }}</div>
                </div>
            @elseif($tab === 'wishlist')
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Wishlist</h2>
                    @if ($wishlistItems->isNotEmpty())
                        <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach ($wishlistItems as $item)
                                <div class="border border-charcoal/10 rounded-md p-2 text-sm">
                                    @if ($item->product && ($path = $item->product->primaryImagePath()))
                                        <img src="{{ \App\Support\Media::url($path, \App\Support\Media::SQUARE_PLACEHOLDER) }}" alt="" class="w-full h-28 object-cover rounded bg-ivory-alt">
                                    @endif
                                    <div class="mt-2 font-medium leading-snug">{{ $item->product?->name ?? 'Product removed' }}</div>
                                    @if ($item->product)
                                        <a href="{{ route('admin.products.edit', $item->product) }}" class="text-xs text-champagne-dark hover:underline">View product</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted">The wishlist is empty.</p>
                    @endif
                </div>
            @else
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Profile</h2>
                    <dl class="text-sm space-y-2">
                        <div class="flex justify-between"><dt class="text-muted">Type</dt><dd>{{ $customer->type->label() }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Email verified</dt><dd>{{ $customer->email_verified_at ? 'Yes' : 'No' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Joined</dt><dd>{{ $customer->created_at?->format('M j, Y') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Last login</dt><dd>{{ $customer->last_login_at?->format('M j, Y H:i') ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Last login IP</dt><dd>{{ $customer->last_login_ip ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Data deleted at</dt><dd>{{ $customer->data_deleted_at?->format('M j, Y') ?? '—' }}</dd></div>
                    </dl>
                </div>
            @endif
        </div>

        <aside class="space-y-4">
            @can('customers.update')
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-3">Manage</h2>
                    <form action="{{ route('admin.customers.update', $customer) }}" method="post" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                @foreach (['active', 'suspended', 'deactivated'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $customer->status->value) === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-admin.checkbox name="marketing_consent" label="Marketing consent" :checked="old('marketing_consent', $customer->marketing_consent)" />
                        <div>
                            <label for="tags">CRM tags <span class="text-muted" aria-hidden="true">(comma separated)</span></label>
                            <input type="text" id="tags" name="tags" value="{{ old('tags', implode(', ', $customer->tags ?? [])) }}" placeholder="vip, bridal, wholesale">
                        </div>
                        <div>
                            <label for="internal_notes">Internal notes</label>
                            <textarea id="internal_notes" name="internal_notes" rows="3">{{ old('internal_notes', $customer->internal_notes) }}</textarea>
                        </div>
                        <button type="submit" class="btn-gold w-full">Save</button>
                    </form>

                    @if ($customer->status->value === 'active')
                        <x-admin.confirm :action="route('admin.customers.impersonate', $customer)" method="POST" title="Browse as this customer?" message="This audited action starts a staff session with the customer’s storefront account." label="Impersonate & browse as customer" confirm-label="Start session" variant="warning" />
                    @endif
                </div>
            @endcan

            <div class="card p-5 text-sm space-y-2">
                <h2 class="font-serif text-lg mb-2">Privacy (GDPR)</h2>
                <p class="text-xs text-muted">Download personal data, or erase it while keeping historic order references intact.</p>
                <a href="{{ route('admin.customers.gdpr', $customer) }}" class="btn-outline w-full text-center text-sm">Download data (JSON)</a>
                @can('customers.delete')
                <x-admin.confirm :action="route('admin.customers.gdpr.erase', $customer)" method="POST" title="Erase personal data?" message="This permanently removes the customer’s personal details while preserving required order records." label="Erase personal data" confirm-label="Erase personal data" />
                @endcan
            </div>
        </aside>
    </div>
@endsection
