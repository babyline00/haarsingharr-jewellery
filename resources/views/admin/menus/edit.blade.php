@extends('admin.layout')

@section('title', 'Edit: '.$menu->name.' — Haarsingharr Admin')

@section('content')
    <x-admin.header :title="$menu->name" :subtitle="ucfirst($menu->location).' navigation — last updated '.$menu->updated_at?->format('d M Y H:i')">
        <x-slot:actions>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn-outline btn-sm">View storefront</a>
            <a href="{{ route('admin.menus.index') }}" class="btn-ghost btn-sm">Back</a>
        </x-slot:actions>
    </x-admin.header>

    @include('admin.menus._form', ['locations' => $locations, 'linkOptions' => $linkOptions])
@endsection
