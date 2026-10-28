@extends('layouts.storefront')

@section('title', 'My Addresses — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-4xl">
            <div class="flex items-center justify-between mb-8">
                <h1 class="font-serif text-3xl md:text-4xl">Addresses</h1>
                <a href="{{ route('account.dashboard') }}" class="btn-ghost text-sm">Back to Account</a>
            </div>

            @if (session('success'))
                <div class="bg-success/10 border border-success/30 text-success px-4 py-3 rounded-md text-sm mb-4">{{ session('success') }}</div>
            @endif

            @if ($addresses->isEmpty())
                <div class="text-center py-12">
                    <p class="font-serif text-xl text-muted">No saved addresses yet</p>
                    <p class="text-sm text-muted mt-2">Add an address to speed up checkout.</p>
                </div>
            @else
                <div class="grid md:grid-cols-2 gap-4 mb-8">
                    @foreach ($addresses as $address)
                        <div class="card p-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ $address->label ?: 'Address' }}</div>
                                    @if ($address->is_default_shipping)
                                        <span class="badge bg-ivory-alt text-charcoal-soft mt-1">Default shipping</span>
                                    @endif
                                    @if ($address->is_default_billing)
                                        <span class="badge bg-ivory-alt text-charcoal-soft mt-1">Default billing</span>
                                    @endif
                                </div>
<<<<<<< HEAD
                                <x-admin.confirm :action="route('account.addresses.destroy', $address)" title="Delete this address?" message="This saved address will be permanently removed from your account." label="Delete address" />
=======
                                <form action="{{ route('account.addresses.destroy', $address) }}" method="post" onsubmit="return confirm('Delete this address?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline">Delete</button>
                                </form>
>>>>>>> origin/main
                            </div>
                            <div class="text-sm mt-3 text-charcoal-soft">{{ $address->fullName() }}</div>
                            <div class="text-xs text-muted mt-1">{{ $address->fullAddress() }}</div>
                            <div class="text-xs text-muted">{{ $address->fullPhone() }}</div>

                            <details class="mt-4">
                                <summary class="text-xs text-champagne-dark cursor-pointer">Edit address</summary>
                                @include('account.addresses._form', [
                                    'address' => $address,
                                    'action' => route('account.addresses.update', $address),
                                    'method' => 'PATCH',
                                    'submit' => 'Save Changes',
                                ])
                            </details>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="card p-6">
                <h2 class="font-serif text-xl mb-4">Add a new address</h2>
                @include('account.addresses._form', [
                    'address' => null,
                    'action' => route('account.addresses.store'),
                    'method' => 'POST',
                    'submit' => 'Add Address',
                ])
            </div>
        </div>
    </section>
@endsection
