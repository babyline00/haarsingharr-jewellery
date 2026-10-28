@extends('admin.layout')

@section('title', 'New Category — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Category</h1>
        <a href="{{ route('admin.categories.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    <form action="{{ route('admin.categories.store') }}" method="post" enctype="multipart/form-data" id="category-form">
        @csrf
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Details</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required value="{{ old('name') }}" autofocus>
                            @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description">Short description</label>
                            <input type="text" id="short_description" name="short_description" value="{{ old('short_description') }}">
                        </div>
                        <div>
                            <label for="description">Description</label>
<<<<<<< HEAD
                            <textarea id="description" name="description" rows="5">{{ old('description') }}</textarea>
=======
                            <textarea id="description" name="description" rows="5" class="ckeditor">{{ old('description') }}</textarea>
>>>>>>> origin/main
                        </div>
                        <div>
                            <label for="banner_image">Banner image</label>
                            <input type="text" id="banner_image" name="banner_image" value="{{ old('banner_image') }}" placeholder="URL or upload below">
                            <input type="file" name="banner_image_file" accept="image/*" class="mt-2 block w-full text-sm">
                            @error('banner_image_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            <div class="mt-4">
                                <label for="icon">Icon (URL or SVG)</label>
                                <input type="text" id="icon" name="icon" value="{{ old('icon') }}" placeholder="Icon URL or SVG path">
                                <input type="file" name="icon_file" accept="image/*,.svg" class="mt-2 block w-full text-sm">
                                @error('icon_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">SEO</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="seo_title">SEO title</label>
                            <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title') }}">
                        </div>
                        <div>
                            <label for="seo_description">SEO description</label>
                            <textarea id="seo_description" name="seo_description" rows="3">{{ old('seo_description') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="parent_id">Parent category</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">— None (top level) —</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('parent_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="auto-generated">
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" @selected(old('status') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort_order">Sort order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', 0) }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="show_in_menu" value="1" @checked(old('show_in_menu'))>
                        Show in navigation menu
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="show_on_homepage" value="1" @checked(old('show_on_homepage'))>
                        Show on homepage
                    </label>
                </div>

                <button type="submit" class="btn-gold w-full">Create Category</button>
            </aside>
        </div>
    </form>
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('category-form');
            if (!form) return;
            const bannerInput = form.querySelector('input[name="banner_image_file"]');
            const iconInput = form.querySelector('input[name="icon_file"]');
            
            async function compressFile(input) {
                if (!input || !input.files.length) return;
                const file = input.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    try {
<<<<<<< HEAD
                        const compressed = await ImageCompressor.compress(file, { maxSizeMB: 8 });
=======
                        const compressed = await ImageCompressor.compress(file, { maxSizeMB: 4 });
>>>>>>> origin/main
                        if (compressed.size < file.size) {
                            const dt = new DataTransfer();
                            dt.items.add(compressed);
                            input.files = dt.files;
                        }
                    } catch (e) {
                        console.warn('Compression failed:', e);
                    }
                }
                const finalFile = input.files[0];
                if (finalFile && finalFile.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB. Please choose a smaller file.');
                    input.value = '';
                }
            }
            
            if (bannerInput) bannerInput.addEventListener('change', () => compressFile(bannerInput));
            if (iconInput) iconInput.addEventListener('change', () => compressFile(iconInput));
        });
    </script>
    @endpush
@endsection
