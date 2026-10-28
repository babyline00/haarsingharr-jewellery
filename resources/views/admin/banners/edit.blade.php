@extends('admin.layout')

@section('title', 'Edit Banner — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="'Edit: '.$banner->name"
                    subtitle="Update the creative, placement and schedule for this banner.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.banners.index')" variant="outline" size="sm" icon="arrow-left">
                All banners
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    @include('admin.banners._form', [
        'banner' => $banner,
        'action' => route('admin.banners.update', $banner),
        'method' => 'PUT',
    ])
@endsection
