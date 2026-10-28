{{-- Report type tabs. Expects nothing. --}}
<div class="flex flex-wrap gap-2 mb-6">
    @php
        $tabs = [
            'admin.reports.sales'          => 'Sales',
<<<<<<< HEAD
            'admin.reports.products'       => 'Products',
            'admin.reports.coupons'        => 'Coupons',
            'admin.reports.tax'            => 'Tax',
            'admin.reports.shipping'       => 'Shipping',
            'admin.reports.payments'       => 'Payments',
            'admin.reports.refunds'        => 'Refunds',
            'admin.reports.exchanges'      => 'Exchanges',
=======
>>>>>>> origin/main
            'admin.reports.inventory'      => 'Inventory',
            'admin.reports.customers'      => 'Customers',
            'admin.reports.staff_activity' => 'Staff Activity',
        ];
    @endphp
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, request()->only(['from', 'to'])) }}"
<<<<<<< HEAD
           class="btn-outline text-sm {{ request()->routeIs($route) ? 'bg-champagne/15 border-champagne/40' : '' }}"
           @if (request()->routeIs($route)) aria-current="page" @endif
        >{{ $label }}</a>
=======
           class="btn-outline text-sm {{ request()->routeIs($route) ? 'bg-champagne/15 border-champagne/40' : '' }}">{{ $label }}</a>
>>>>>>> origin/main
    @endforeach
</div>
