@extends('admin.layout')

@section('title', 'Navigation Menus — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Navigation menus"
                    subtitle="Header, footer and mobile navigation rendered on the storefront when available, with a built-in fallback.">
        <x-slot:actions>
            @can('menus.create')
                <x-admin.button :href="route('admin.menus.create')" size="sm" icon="plus">New Menu</x-admin.button>
            @endcan
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Menus" :value="number_format($stats['total'])" icon="list" />
        <x-admin.stat label="Header (main)" :value="number_format($stats['main'])" icon="menu" />
        <x-admin.stat label="Footer" :value="number_format($stats['footer'])" icon="align-bottom" />
        <x-admin.stat label="Mobile" :value="number_format($stats['mobile'])" icon="smartphone" />
    </div>

    <x-admin.card :padding="false">
        <x-admin.table caption="Menus by location">
            <thead>
                <tr>
                    <x-admin.th label="Name" />
                    <x-admin.th label="Location" />
                    <x-admin.th label="Items" />
                    <x-admin.th label="Updated" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($menus as $menu)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            <a href="{{ route('admin.menus.edit', $menu) }}" class="font-medium text-champagne-dark hover:underline">{{ $menu->name }}</a>
                        </td>
                        <td class="p-3 text-xs uppercase tracking-widest text-muted">{{ ucfirst($menu->location) }}</td>
                        <td class="p-3 tabular-nums">{{ count($menu->items ?? []) }}</td>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $menu->updated_at?->format('M j, Y') }}</td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.menus.edit', $menu) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                            @can('menus.delete')
                                <x-admin.confirm :action="route('admin.menus.destroy', $menu)"
                                                 method="DELETE"
                                                 title="Delete this menu?"
                                                 message="The storefront nav falls back to the built-in links immediately."
                                                 label="Delete"
                                                 confirm-label="Delete menu" />
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-admin.empty icon="list" title="Menus not configured"
                                           description="Create a header or footer menu — storefront falls back to defaults until you do.">
                                @can('menus.create')
                                    <x-slot:action>
                                        <x-admin.button :href="route('admin.menus.create')" size="sm" icon="plus">New Menu</x-admin.button>
                                    </x-slot:action>
                                @endcan
                            </x-admin.empty>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>
    </x-admin.card>
@endsection
