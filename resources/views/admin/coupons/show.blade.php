@extends('admin.layout')

@section('title', $coupon->code.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="$coupon->code" :subtitle="ucfirst(str_replace('_', ' ', $coupon->type)).' coupon — '.($coupon->type === 'percentage' ? rtrim(rtrim(number_format($coupon->value, 2), '0'), '.').'%' : 'Rs. '.number_format($coupon->value))">
        <x-slot:actions>
            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn-outline btn-sm">Edit</a>
            <a href="{{ route('admin.coupons.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Times used" :value="number_format($coupon->used_count).($coupon->usage_limit_total ? ' / '.$coupon->usage_limit_total : '')" icon="tag" />
        <x-admin.stat label="Unique customers" :value="number_format($stats['unique_customers'])" icon="users" />
        <x-admin.stat label="Discount given" :value="'Rs. '.number_format($stats['total_discount'], 2)" icon="wallet" />
        <x-admin.stat label="Avg order value" :value="$stats['avg_order_value'] > 0 ? 'Rs. '.number_format($stats['avg_order_value'], 0) : '—'" icon="shopping-bag" />
    </div>

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <x-admin.card :padding="false">
                <div class="p-5 pb-3">
                    <h2 class="font-serif text-lg">Redemptions</h2>
                </div>
                <x-admin.table caption="Coupon redemptions">
                    <thead>
                        <tr>
                            <x-admin.th label="Order" />
                            <x-admin.th label="Customer" />
                            <x-admin.th label="Discount" align="right" />
                            <x-admin.th label="Date" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($redemptions as $redemption)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3">
                                    @if ($redemption->order)
                                        <a href="{{ route('admin.orders.show', $redemption->order) }}" class="font-medium text-champagne-dark hover:underline">{{ $redemption->order->number }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if ($redemption->user)
                                        <a href="{{ route('admin.customers.show', $redemption->user) }}" class="hover:underline">{{ $redemption->user->email }}</a>
                                    @elseif ($redemption->order?->guest_email)
                                        {{ $redemption->order->guest_email }} <span class="text-xs text-muted">(guest)</span>
                                    @else
                                        <span class="text-muted">Guest</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right"><x-admin.money :amount="$redemption->discount_applied ?? 0" /></td>
                                <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $redemption->created_at?->format('M j, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-admin.empty icon="tag" title="Not redeemed yet"
                                                   description="This code has no redemptions recorded yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-admin.table>
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$redemptions" />
                </div>
            </x-admin.card>
        </div>

        <aside class="space-y-4">
            <x-admin.card>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-serif text-lg mr-2">Validity</h2>
                    <x-admin.status-pill :status="$coupon->status" />
                </div>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Starts</dt><dd>{{ $coupon->starts_at?->format('M j, Y H:i') ?? 'Immediately' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Expires</dt><dd>{{ $coupon->expires_at?->format('M j, Y H:i') ?? 'Never' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Valid now</dt><dd>{{ $coupon->isValid() ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </x-admin.card>

            <x-admin.card>
                <h2 class="font-serif text-lg mb-3">Restrictions</h2>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Minimum spend</dt><dd>{{ $coupon->min_order_value ? 'Rs. '.number_format($coupon->min_order_value, 0) : 'None' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Max discount</dt><dd>{{ $coupon->max_discount_amount ? 'Rs. '.number_format($coupon->max_discount_amount, 0) : 'None' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Per-customer limit</dt><dd>{{ $coupon->usage_limit_per_customer ?? 'Unlimited' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">First order only</dt><dd>{{ $coupon->first_order_only ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Excludes sale items</dt><dd>{{ $coupon->exclude_sale_items ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">First redeemed</dt><dd>{{ $stats['first_redemption'] ? \Illuminate\Support\Carbon::parse($stats['first_redemption'])->format('M j, Y') : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Last redeemed</dt><dd>{{ $stats['last_redemption'] ? \Illuminate\Support\Carbon::parse($stats['last_redemption'])->format('M j, Y') : '—' }}</dd></div>
                </dl>
            </x-admin.card>
        </aside>
    </div>
@endsection
