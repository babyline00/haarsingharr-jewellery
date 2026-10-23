{{-- Report type tabs. Expects nothing. --}}
<div class="flex flex-wrap gap-2 mb-6">
    @php
        $tabs = [
            'admin.reports.sales'          => 'Sales',
            'admin.reports.inventory'      => 'Inventory',
            'admin.reports.customers'      => 'Customers',
            'admin.reports.staff_activity' => 'Staff Activity',
        ];
    @endphp
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, request()->only(['from', 'to'])) }}"
           class="btn-outline text-sm {{ request()->routeIs($route) ? 'bg-champagne/15 border-champagne/40' : '' }}">{{ $label }}</a>
    @endforeach
</div>
