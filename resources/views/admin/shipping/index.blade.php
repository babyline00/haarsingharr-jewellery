@extends('admin.layout')

@section('title', 'Shipping & Fulfilment — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Shipping &amp; fulfilment"
                    subtitle="Zones, rates, courier tracking, labels and cash-on-delivery reconciliation." />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Awaiting pickup" :value="number_format($stats['awaiting'])" icon="clock" />
        <x-admin.stat label="In transit" :value="number_format($stats['in_transit'])" icon="truck" />
        <x-admin.stat label="Delivered today" :value="number_format($stats['delivered_today'])" icon="check-circle" />
        <x-admin.stat label="COD to reconcile" :value="number_format($stats['cod_orders'])"
                      :delta="'Rs. '.number_format($stats['cod_value'])" trend="flat" icon="wallet" />
    </div>

    <div class="grid lg:grid-cols-2 gap-5 mb-5">
        <x-admin.card title="Shipping zones" subtitle="Rates are additive; the fallback settings below remain in use until zones are connected to checkout.">
            <x-slot:actions>
                @can('shipping.update')
                    <x-admin.button type="button" variant="outline" size="sm" icon="plus"
                                    x-on:click="$dispatch('open-modal-zone-create')">Add zone</x-admin.button>
                @endcan
            </x-slot:actions>

            <div class="space-y-4">
                @forelse ($zones as $zone)
                    <div class="border border-charcoal/10 rounded-lg overflow-hidden">
                        <div class="p-3 bg-ivory-alt/40 flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium">{{ $zone->name }}</div>
                                <div class="text-xs text-muted">{{ $zone->code }} · {{ $zone->countryList() }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-admin.status-pill :status="$zone->is_active ? 'active' : 'inactive'" :label="$zone->is_active ? 'Active' : 'Inactive'" />
                                @can('shipping.update')
                                    <button type="button" class="text-xs text-champagne-dark hover:underline"
                                            x-on:click="$dispatch('open-modal-zone-edit-{{ $zone->id }}')">Edit</button>
                                    <x-admin.confirm :action="route('admin.shipping.zones.destroy', $zone)"
                                                     method="DELETE"
                                                     title="Remove this zone?"
                                                     message="Its rates will also be deleted. Shipments already created are unaffected."
                                                     label="Remove"
                                                     confirm-label="Remove zone" />
                                @endcan
                            </div>
                        </div>

                        @if ($zone->rates->isEmpty())
                            <div class="p-3 text-sm text-muted">No rates in this zone.</div>
                        @else
                            <div class="divide-y divide-charcoal/5">
                                @foreach ($zone->rates as $rate)
                                    <div class="p-3 flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-sm">{{ $rate->name }} <span class="text-xs text-muted capitalize">· {{ $rate->method }}</span></div>
                                            <div class="text-xs text-muted">
                                                Rs. {{ number_format((float) $rate->rate, 2) }}
                                                @if ($rate->free_over) · free over Rs. {{ number_format((float) $rate->free_over) }} @endif
                                                · {{ $rate->etaLabel() }}
                                                @if ($rate->min_order_total) · min Rs. {{ number_format((float) $rate->min_order_total) }} @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-admin.status-pill :status="$rate->is_active ? 'active' : 'inactive'" :label="$rate->is_active ? 'On' : 'Off'" />
                                            @can('shipping.update')
                                                <button type="button" class="text-xs text-champagne-dark hover:underline"
                                                        x-on:click="$dispatch('open-modal-rate-edit-{{ $rate->id }}')">Edit</button>
                                                <x-admin.confirm :action="route('admin.shipping.rates.destroy', $rate)"
                                                                 method="DELETE"
                                                                 title="Remove this rate?"
                                                                 message="This rate will no longer be available for new orders."
                                                                 label="Remove"
                                                                 confirm-label="Remove rate" />
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @can('shipping.update')
                            <div class="px-3 py-2 border-t border-charcoal/5">
                                <button type="button" class="text-xs text-champagne-dark hover:underline"
                                        x-on:click="$dispatch('open-modal-rate-create-{{ $zone->id }}')">Add rate to {{ $zone->name }}</button>
                            </div>
                        @endcan
                    </div>
                @empty
                    <x-admin.empty icon="truck" title="No shipping zones"
                                   description="Add a zone to start defining rates by destination." />
                @endforelse
            </div>
        </x-admin.card>

        <x-admin.card title="COD reconciliation" subtitle="Delivered COD orders still showing an unpaid balance." :padding="false">
            @if ($stats['cod_orders'] === 0)
                <div class="p-5">
                    <x-admin.empty icon="wallet" title="All COD orders reconciled"
                                   description="No delivered cash-on-delivery orders are waiting for payment." />
                </div>
            @else
                <div class="divide-y divide-charcoal/5">
                    @foreach ($codOrders as $codOrder)
                        <a href="{{ route('admin.orders.show', $codOrder) }}"
                           class="flex items-center justify-between gap-3 p-3 hover:bg-ivory-alt/30 transition-colors">
                            <div>
                                <div class="text-sm font-medium">{{ $codOrder->number }}</div>
                                <div class="text-xs text-muted">{{ $codOrder->created_at->format('j M Y') }} · {{ $codOrder->guest_email ?? $codOrder->user?->email }}</div>
                            </div>
                            <x-admin.money :amount="$codOrder->grand_total" :decimals="2" />
                        </a>
                    @endforeach
                </div>
                <div class="p-3 border-t border-charcoal/5 text-xs text-muted">
                    Record payment from the order page after the courier settles.
                </div>
            @endif
        </x-admin.card>
    </div>

    <x-admin.card title="Shipments" subtitle="Filter, edit tracking and print courier labels." :padding="false">
        <form method="get" class="p-4 border-b border-charcoal/5 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <x-admin.field name="q" label="Search">
                    <x-admin.input name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Order #, tracking, courier" />
                </x-admin.field>
            </div>
            <div class="w-full sm:w-44">
                <x-admin.field name="status" label="Status">
                    <x-admin.select name="status" :selected="$filters['status'] ?? ''" placeholder="All statuses"
                                    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => ucwords(str_replace('_', ' ', $status))])->all()" />
                </x-admin.field>
            </div>
            <div class="w-full sm:w-40">
                <x-admin.field name="courier" label="Courier">
                    <x-admin.select name="courier" :selected="$filters['courier'] ?? ''" placeholder="All couriers"
                                    :options="$couriers->mapWithKeys(fn ($courier) => [$courier => ucfirst($courier)])->all()" />
                </x-admin.field>
            </div>
            <div class="w-full sm:w-36">
                <x-admin.field name="date_from" label="From">
                    <x-admin.input name="date_from" type="date" :value="$filters['date_from'] ?? ''" />
                </x-admin.field>
            </div>
            <div class="w-full sm:w-36">
                <x-admin.field name="date_to" label="To">
                    <x-admin.input name="date_to" type="date" :value="$filters['date_to'] ?? ''" />
                </x-admin.field>
            </div>
            <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>
            @if (array_filter($filters ?? []))
                <x-admin.button :href="route('admin.shipping.index')" variant="ghost" size="sm">Clear</x-admin.button>
            @endif
        </form>

        <x-admin.table caption="Shipments">
            <thead>
                <tr>
                    <x-admin.th label="Order" />
                    <x-admin.th label="Courier" />
                    <x-admin.th label="Tracking" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Est. delivery" />
                    <x-admin.th label="Cost" align="right" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($shipments as $shipment)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            @if ($shipment->order)
                                <a href="{{ route('admin.orders.show', $shipment->order) }}" class="font-medium text-champagne-dark hover:underline">
                                    {{ $shipment->order->number }}
                                </a>
                                <span class="block text-xs text-muted">{{ $shipment->order->guest_email ?? $shipment->order->user?->email }}</span>
                            @else
                                <span class="text-muted">Deleted order</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm capitalize">{{ $shipment->courier }}<span class="block text-xs text-muted">{{ $shipment->method ?? 'Standard' }}</span></td>
                        <td class="p-3 text-xs text-charcoal-soft">{{ $shipment->tracking_number }}</td>
                        <td class="p-3"><x-admin.status-pill :status="$shipment->status" :label="$shipment->statusLabel()" /></td>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $shipment->estimated_delivery_at?->format('j M Y') ?? '—' }}</td>
                        <td class="p-3 text-right text-sm"><x-admin.money :amount="$shipment->shipping_cost ?? 0" :decimals="2" /></td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.shipping.shipments.label', $shipment) }}" class="text-xs text-champagne-dark hover:underline">Label</a>
                            <span class="text-muted/40 mx-1">·</span>
                            @can('shipping.update')
                                <button type="button" class="text-xs text-champagne-dark hover:underline"
                                        x-on:click="$dispatch('open-modal-shipment-edit-{{ $shipment->id }}')">Edit</button>
                                <span class="text-muted/40 mx-1">·</span>
                                <x-admin.confirm :action="route('admin.shipping.shipments.destroy', $shipment)"
                                                 method="DELETE"
                                                 title="Remove this shipment?"
                                                 message="The shipment is deleted and the order tracking fields are cleared."
                                                 label="Remove"
                                                 confirm-label="Remove shipment" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-admin.empty icon="truck" title="No shipments found"
                                           description="Add a shipment from an order detail page, or clear the filters." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($shipments->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$shipments" />
            </div>
        @endif
    </x-admin.card>

    <x-admin.card title="Fallback shipping settings" subtitle="Used when no zone rate applies." class="mt-5">
        <form method="POST" action="{{ route('admin.shipping.store') }}" class="space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($settings as $field)
                    @if ($field['type'] === 'boolean')
                        <x-admin.checkbox :name="'shipping['.$field['key'].']'" :label="$field['label']" :checked="(bool) $field['value']" />
                    @else
                        <x-admin.field :name="'shipping['.$field['key'].']'" :label="$field['label']">
                            <x-admin.input :name="'shipping['.$field['key'].']'"
                                           :type="$field['type'] === 'number' ? 'number' : 'text'"
                                           :step="$field['type'] === 'number' ? '0.01' : null"
                                           :value="$field['value']" />
                        </x-admin.field>
                    @endif
                @endforeach
            </div>
            <div class="flex justify-end">
                <x-admin.button type="submit" size="sm" icon="save">Save fallback settings</x-admin.button>
            </div>
        </form>
    </x-admin.card>

    {{-- Zone create --}}
    @can('shipping.update')
        <x-admin.modal name="zone-create" title="Add shipping zone" max-width="max-w-lg">
            <form method="POST" action="{{ route('admin.shipping.zones.store') }}" class="space-y-3">
                @csrf
                <x-admin.field name="name" label="Zone name" required>
                    <x-admin.input name="name" placeholder="Punjab, Sindh, International…" required />
                </x-admin.field>
                <x-admin.field name="countries" label="Countries" hint="Comma-separated ISO codes. Leave blank for all countries.">
                    <x-admin.input name="countries" placeholder="PK, AE, GB" />
                </x-admin.field>
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-admin.field name="sort_order" label="Sort order">
                        <x-admin.input name="sort_order" type="number" min="0" value="0" />
                    </x-admin.field>
                    <div class="flex items-center">
                        <x-admin.checkbox name="is_active" label="Active" checked />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <x-admin.button type="button" variant="outline" size="sm"
                                    x-on:click="$dispatch('close-modal-zone-create')">Cancel</x-admin.button>
                    <x-admin.button type="submit" size="sm" icon="plus">Create zone</x-admin.button>
                </div>
            </form>
        </x-admin.modal>

        @foreach ($zones as $zone)
            <x-admin.modal name="zone-edit-{{ $zone->id }}" title="Edit shipping zone" max-width="max-w-lg">
                <form method="POST" action="{{ route('admin.shipping.zones.update', $zone) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <x-admin.field name="name" label="Zone name" required>
                        <x-admin.input name="name" :value="$zone->name" required />
                    </x-admin.field>
                    <x-admin.field name="countries" label="Countries" hint="Comma-separated ISO codes. Leave blank for all countries.">
                        <x-admin.input name="countries" :value="implode(', ', $zone->countries ?? [])" />
                    </x-admin.field>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="sort_order" label="Sort order">
                            <x-admin.input name="sort_order" type="number" min="0" :value="$zone->sort_order" />
                        </x-admin.field>
                        <div class="flex items-center">
                            <x-admin.checkbox name="is_active" label="Active" :checked="$zone->is_active" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-zone-edit-{{ $zone->id }}')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="save">Save zone</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>

            <x-admin.modal name="rate-create-{{ $zone->id }}" title="Add rate to {{ $zone->name }}" max-width="max-w-xl">
                <form method="POST" action="{{ route('admin.shipping.rates.store', $zone) }}" class="space-y-3">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="name" label="Rate name" required>
                            <x-admin.input name="name" placeholder="Standard delivery" required />
                        </x-admin.field>
                        <x-admin.field name="method" label="Method" required>
                            <x-admin.input name="method" value="standard" required />
                        </x-admin.field>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <x-admin.field name="rate" label="Rate (PKR)" required>
                            <x-admin.input name="rate" type="number" min="0" step="0.01" value="0" required />
                        </x-admin.field>
                        <x-admin.field name="free_over" label="Free over (PKR)">
                            <x-admin.input name="free_over" type="number" min="0" step="0.01" />
                        </x-admin.field>
                        <x-admin.field name="min_order_total" label="Minimum order (PKR)">
                            <x-admin.input name="min_order_total" type="number" min="0" step="0.01" />
                        </x-admin.field>
                    </div>
                    <div class="grid sm:grid-cols-4 gap-3">
                        <x-admin.field name="eta_min_days" label="ETA min (days)">
                            <x-admin.input name="eta_min_days" type="number" min="0" />
                        </x-admin.field>
                        <x-admin.field name="eta_max_days" label="ETA max (days)">
                            <x-admin.input name="eta_max_days" type="number" min="0" />
                        </x-admin.field>
                        <x-admin.field name="min_weight_grams" label="Min weight (g)">
                            <x-admin.input name="min_weight_grams" type="number" min="0" step="0.01" />
                        </x-admin.field>
                        <x-admin.field name="max_weight_grams" label="Max weight (g)">
                            <x-admin.input name="max_weight_grams" type="number" min="0" step="0.01" />
                        </x-admin.field>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <x-admin.checkbox name="is_active" label="Active" checked />
                        <x-admin.input name="sort_order" type="number" min="0" value="0" class="max-w-[110px]" aria-label="Sort order" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-rate-create-{{ $zone->id }}')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="plus">Add rate</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>

            @foreach ($zone->rates as $rate)
                <x-admin.modal name="rate-edit-{{ $rate->id }}" title="Edit rate" max-width="max-w-xl">
                    <form method="POST" action="{{ route('admin.shipping.rates.update', $rate) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-admin.field name="name" label="Rate name" required>
                                <x-admin.input name="name" :value="$rate->name" required />
                            </x-admin.field>
                            <x-admin.field name="method" label="Method" required>
                                <x-admin.input name="method" :value="$rate->method" required />
                            </x-admin.field>
                        </div>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <x-admin.field name="rate" label="Rate (PKR)" required>
                                <x-admin.input name="rate" type="number" min="0" step="0.01" :value="$rate->rate" required />
                            </x-admin.field>
                            <x-admin.field name="free_over" label="Free over (PKR)">
                                <x-admin.input name="free_over" type="number" min="0" step="0.01" :value="$rate->free_over" />
                            </x-admin.field>
                            <x-admin.field name="min_order_total" label="Minimum order (PKR)">
                                <x-admin.input name="min_order_total" type="number" min="0" step="0.01" :value="$rate->min_order_total" />
                            </x-admin.field>
                        </div>
                        <div class="grid sm:grid-cols-4 gap-3">
                            <x-admin.field name="eta_min_days" label="ETA min (days)">
                                <x-admin.input name="eta_min_days" type="number" min="0" :value="$rate->eta_min_days" />
                            </x-admin.field>
                            <x-admin.field name="eta_max_days" label="ETA max (days)">
                                <x-admin.input name="eta_max_days" type="number" min="0" :value="$rate->eta_max_days" />
                            </x-admin.field>
                            <x-admin.field name="min_weight_grams" label="Min weight (g)">
                                <x-admin.input name="min_weight_grams" type="number" min="0" step="0.01" :value="$rate->min_weight_grams" />
                            </x-admin.field>
                            <x-admin.field name="max_weight_grams" label="Max weight (g)">
                                <x-admin.input name="max_weight_grams" type="number" min="0" step="0.01" :value="$rate->max_weight_grams" />
                            </x-admin.field>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <x-admin.checkbox name="is_active" label="Active" :checked="$rate->is_active" />
                            <x-admin.input name="sort_order" type="number" min="0" :value="$rate->sort_order" class="max-w-[110px]" aria-label="Sort order" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <x-admin.button type="button" variant="outline" size="sm"
                                            x-on:click="$dispatch('close-modal-rate-edit-{{ $rate->id }}')">Cancel</x-admin.button>
                            <x-admin.button type="submit" size="sm" icon="save">Save rate</x-admin.button>
                        </div>
                    </form>
                </x-admin.modal>
            @endforeach
        @endforeach
    @endcan

    @can('shipping.update')
        @foreach ($shipments as $shipment)
            <x-admin.modal name="shipment-edit-{{ $shipment->id }}" title="Edit shipment" max-width="max-w-lg">
                <form method="POST" action="{{ route('admin.shipping.shipments.update', $shipment) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="courier" label="Courier" required>
                            <x-admin.input name="courier" :value="$shipment->courier" required />
                        </x-admin.field>
                        <x-admin.field name="method" label="Service">
                            <x-admin.input name="method" :value="$shipment->method" />
                        </x-admin.field>
                    </div>
                    <x-admin.field name="tracking_number" label="Tracking number" required>
                        <x-admin.input name="tracking_number" :value="$shipment->tracking_number" required />
                    </x-admin.field>
                    <x-admin.field name="tracking_url" label="Tracking URL">
                        <x-admin.input name="tracking_url" type="url" :value="$shipment->tracking_url" />
                    </x-admin.field>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="status" label="Status" required>
                            <x-admin.select name="status" :selected="$shipment->status"
                                            :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => ucwords(str_replace('_', ' ', $status))])->all()" />
                        </x-admin.field>
                        <x-admin.field name="estimated_delivery_at" label="Est. delivery">
                            <x-admin.input name="estimated_delivery_at" type="date" :value="$shipment->estimated_delivery_at?->toDateString()" />
                        </x-admin.field>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="shipping_cost" label="Cost (PKR)">
                            <x-admin.input name="shipping_cost" type="number" min="0" step="0.01" :value="$shipment->shipping_cost" />
                        </x-admin.field>
                        <x-admin.field name="weight_grams" label="Weight (g)">
                            <x-admin.input name="weight_grams" type="number" min="0" step="0.01" :value="$shipment->weight_grams" />
                        </x-admin.field>
                    </div>
                    <x-admin.field name="notes" label="Notes">
                        <x-admin.textarea name="notes" rows="2" :value="$shipment->notes" />
                    </x-admin.field>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-shipment-edit-{{ $shipment->id }}')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="save">Save shipment</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endforeach
    @endcan
@endsection
