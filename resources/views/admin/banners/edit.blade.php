@extends('admin.layout')

@section('title', 'Edit Banner — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header :title="'Edit: '.$banner->name"
                    subtitle="Update the creative, placement and schedule for this banner.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.banners.index')" variant="outline" size="sm" icon="arrow-left">
                All banners
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Edit Banner</h1>
            <p class="text-sm text-muted mt-1">{{ $banner->name }}</p>
        </div>
        <a href="{{ route('admin.banners.index') }}" class="btn-ghost text-sm">Back</a>
    </div>
>>>>>>> origin/main

    @include('admin.banners._form', [
        'banner' => $banner,
        'action' => route('admin.banners.update', $banner),
        'method' => 'PUT',
    ])
<<<<<<< HEAD
=======

    <div class="card p-5 mt-6">
        <h2 class="font-serif text-lg mb-4">Quick image upload</h2>
        <form action="{{ route('admin.banners.image.upload', $banner) }}" method="post" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label for="upload_image_file">Image file</label>
                <input type="file" id="upload_image_file" name="image_file" accept="image/*" required>
            </div>
            <div>
                <label for="upload_target">Target</label>
                <select id="upload_target" name="target">
                    <option value="image">Desktop image</option>
                    <option value="mobile_image">Mobile image</option>
                </select>
            </div>
            <button type="submit" class="btn-gold">Upload Image</button>
        </form>
    </div>
>>>>>>> origin/main
@endsection
