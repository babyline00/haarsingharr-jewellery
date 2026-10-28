@extends('admin.layout')

@section('title', 'New Menu — Haarsingharr Admin')

@section('content')
    <x-admin.header title="New navigation menu"
                    subtitle="Choose a storefront location, then compose items and dropdowns.">
        <x-slot:actions>
            <a href="{{ route('admin.menus.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    @include('admin.menus._form', ['locations' => $locations, 'linkOptions' => $linkOptions])
@endsection
