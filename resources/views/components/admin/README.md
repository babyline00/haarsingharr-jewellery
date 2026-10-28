# Admin Blade components

All components live in `resources/views/components/admin/` and are used as `<x-admin.*>`.
They deliberately render the existing `app.css` classes (`.card`, `.btn-gold`, `.badge-*`,
`.table`, `.pagination`, `.modal`, `.tabs`, `.empty-state`) so there is one visual language.

## Layout

```blade
<x-admin.header title="Orders" subtitle="Every order, newest first.">
    <x-slot:actions>
        <x-admin.button :href="route('admin.orders.index')" variant="outline" size="sm" icon="refresh">
            Refresh
        </x-admin.button>
    </x-slot:actions>
</x-admin.header>
```

Breadcrumbs are rendered once by `admin/layout.blade.php` from the route name
(`App\Support\Admin\Breadcrumbs`), so pages must not add their own.

- `header` — title, subtitle, `actions` slot, optional `meta` slot
- `card` — `title`, `subtitle`, `actions` slot, `footer` slot, `flush`, `padding`
- `section` — titled block inside a form
- `tabs` — `items: [['label' => 'All', 'url' => '…', 'active' => true]]`
- `stat` — `label`, `value`, `delta`, `trend` (`up|down|flat`), `icon`, `href`
- `empty` — `title`, `description`, `icon`, `action` slot
- `breadcrumbs` — automatic; no props

## Data

```blade
<x-admin.filter-bar>
    <div class="flex-1 min-w-[220px]">
        <label for="order-search" class="sr-only">Search orders</label>
        <x-admin.input id="order-search" name="q" type="search" :value="$filters['q'] ?? ''" />
    </div>
    <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Filter</x-admin.button>
</x-admin.filter-bar>

<x-admin.card :padding="false">
    <x-admin.table caption="Orders">
        <thead><tr><x-admin.th label="Order" /><x-admin.th label="Total" align="right" /></tr></thead>
        <tbody class="divide-y divide-charcoal/5">
            @forelse ($orders as $order)
                <tr>
                    <td class="p-3">{{ $order->number }}</td>
                    <td class="p-3 text-right"><x-admin.money :amount="$order->grand_total" /></td>
                </tr>
            @empty
                <tr><td colspan="2"><x-admin.empty icon="shopping-bag" title="No orders found" /></td></tr>
            @endforelse
        </tbody>
    </x-admin.table>

    @if ($orders->total() > 0)
        <div class="px-4 border-t border-charcoal/5">
            <x-admin.pagination :paginator="$orders" />
        </div>
    @endif
</x-admin.card>
```

- `th` — `label`, `field` (adds sorting links + `aria-sort`), `align`
- `pagination` — shows the range and only renders links when there are pages
- `status-pill` — `status`, optional `label`; maps order/payment/refund statuses to badges
- `money` — `amount`, `currency` (default PKR), `decimals` (default 0)
- `nav-group` / `nav-count` — sidebar section (persisted open state) and badge count

## Forms

```blade
<x-admin.error-summary />

<x-admin.field name="code" label="Coupon code" :required="true" hint="Letters and numbers only.">
    <x-admin.input name="code" :value="$coupon->code" required />
</x-admin.field>

<x-admin.field name="type" label="Type">
    <x-admin.select name="type" :selected="$coupon->type"
                    :options="['percentage' => 'Percentage', 'fixed' => 'Fixed amount']" />
</x-admin.field>

<x-admin.field name="description" label="Description">
    <x-admin.textarea name="description" :value="$coupon->description" />
</x-admin.field>

<x-admin.checkbox name="is_active" label="Active" :checked="$coupon->is_active" />
```

`field` wires the label, hint, error text, `aria-invalid`, and `aria-describedby`
automatically (ids default to `field-{name}`). `input`/`select`/`textarea` read
`old()` values and support dot notation for nested names (`settings[general][name]`).

## Feedback

```blade
<x-admin.button x-on:click="$dispatch('open-modal-invite')" icon="plus">Invite</x-admin.button>

<x-admin.modal name="invite" title="Invite a team member">
    <x-admin.field name="email" label="Email"><x-admin.input name="email" type="email" /></x-admin.field>
</x-admin.modal>
```

```blade
<x-admin.confirm :action="route('admin.coupons.destroy', $coupon)"
                 method="DELETE"
                 title="Archive this coupon?"
                 message="The code will stop working immediately."
                 label="Archive"
                 confirm-label="Archive coupon" />
```

`confirm` replaces native `confirm()`: labelled alert dialog, ESC, focus on the confirm
button, focus restored to the trigger, body scroll lock. `modal` captures the trigger
element and restores focus on close.

## Icons

`<x-admin.icon name="package" :size="18" />` — stroke SVG set in
`app/View/Components/Admin/Icon.php`. Unknown names fall back to `info`.
