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
<<<<<<< HEAD
        'checkout'  => 'Checkout Rules',
        'email'     => 'Email / From',
        'notifications' => 'Notifications',
        'homepage'  => 'Homepage',
        'legal'     => 'Legal Pages',
        'system'    => 'System',
=======
        'marketing' => 'Marketing & Gift Popup',
>>>>>>> origin/main
    ];
@endphp

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Store Settings" subtitle="Brand, tax, inventory and review preferences.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.settings.payments')" variant="outline" size="sm" icon="credit-card">
                Payment Settings
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    @can('settings.update')
        <form action="{{ route('admin.settings.update') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="grid lg:grid-cols-2 gap-6">
                @foreach ($groups as $module => $fields)
                    <x-admin.card :title="$titles[$module] ?? ucfirst($module)"
                                  class="{{ in_array($module, ['general'], true) ? 'lg:col-span-2' : '' }}">
                        <div class="grid md:grid-cols-2 gap-4">
                            @include('admin.settings._fields', ['module' => $module, 'fields' => $fields])
                        </div>
                    </x-admin.card>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <x-admin.button type="submit" icon="save">Save Settings</x-admin.button>
            </div>
        </form>
    @else
        <p class="text-sm text-muted">You do not have permission to change store settings.</p>
    @endcan

    {{-- Mail status + test send (outside the settings form) --}}
    <div class="card p-5 mt-6">
        <h2 class="font-serif text-lg mb-3">Outgoing mail (SMTP)</h2>
        <div class="grid md:grid-cols-3 gap-4 mb-4 text-sm">
            <div>
                <p class="text-xs uppercase tracking-widest text-muted">Mailer</p>
                <p class="font-medium">{{ config('mail.default') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-muted">Host</p>
                <p class="font-medium break-words">{{ config('mail.mailers.' . config('mail.default') . '.host') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-muted">From</p>
                <p class="font-medium break-words">{{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;</p>
            </div>
        </div>
        <p class="text-xs text-muted mb-3">
            SMTP credentials live in the server environment (cPanel → Email Accounts / .env).
            From-name and from-address overrides can be saved in the <em>Email / From</em> card above.
        </p>
        @can('settings.update')
            <form action="{{ route('admin.settings.email-test') }}" method="post" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="text-xs uppercase tracking-widest text-muted">
                    <span class="block mb-1">Send a test email to</span>
                    <input type="email" name="email" required value="{{ old('email', config('mail.from.address')) }}" class="w-64">
                </label>
                <button type="submit" class="btn-outline text-sm">Send test email</button>
            </form>
        @endcan
    </div>
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Store Settings</h1>
            <p class="text-sm text-muted mt-1">Brand, tax, inventory and review preferences.</p>
        </div>
        <a href="{{ route('admin.settings.payments') }}" class="btn-outline text-sm">Payment Settings</a>
    </div>

    @can('settings.update')
    <form action="{{ route('admin.settings.update') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="grid lg:grid-cols-2 gap-6">
            @foreach ($groups as $module => $fields)
                <div id="settings-{{ $module }}" class="card p-5 scroll-mt-24 {{ in_array($module, ['general'], true) ? 'lg:col-span-2' : '' }}">
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
>>>>>>> origin/main
@endsection
