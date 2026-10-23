@extends('admin.layout')

@section('title', 'Store Settings — Haarsingharr Admin')

@php
    $titles = [
        'general'   => 'General',
        'branding'  => 'Branding',
        'tax'       => 'Tax',
        'inventory' => 'Inventory',
        'reviews'   => 'Reviews',
        'social'    => 'Social Profiles',
        'shipping'  => 'Shipping',
        'orders'    => 'Orders',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Store Settings</h1>
            <p class="text-sm text-muted mt-1">Brand, tax, inventory and review preferences.</p>
        </div>
        <a href="{{ route('admin.settings.payments') }}" class="btn-outline text-sm">Payment Settings</a>
    </div>

    @can('settings.update')
    <form action="{{ route('admin.settings.update') }}" method="post">
        @csrf
        <div class="grid lg:grid-cols-2 gap-6">
            @foreach ($groups as $module => $fields)
                <div class="card p-5 {{ in_array($module, ['general'], true) ? 'lg:col-span-2' : '' }}">
                    <h2 class="font-serif text-lg mb-4">{{ $titles[$module] ?? ucfirst($module) }}</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        @include('admin.settings._fields', ['module' => $module, 'fields' => $fields])
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="btn-gold">Save Settings</button>
        </div>
    </form>
    @else
        <p class="text-sm text-muted">You do not have permission to change store settings.</p>
    @endcan
@endsection
