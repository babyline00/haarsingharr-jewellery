@extends('admin.layout')

@section('title', 'New Page — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">New Page</h1>
        <a href="{{ route('admin.pages.index') }}" class="btn-ghost text-sm">Cancel</a>
    </div>

    <form action="{{ route('admin.pages.store') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Content</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required value="{{ old('title') }}" autofocus>
                            @error('title') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="excerpt">Excerpt</label>
                            <textarea id="excerpt" name="excerpt" rows="2">{{ old('excerpt') }}</textarea>
                        </div>
                        <div>
                            <label for="content">Body (HTML allowed)</label>
                            <textarea id="content" name="content" rows="14" class="ckeditor font-mono text-xs">{{ old('content') }}</textarea>
                        </div>
                        <div>
                            <label for="sections">Builder sections (JSON)</label>
                            <textarea id="sections" name="sections" rows="4" class="font-mono text-xs" placeholder='[{"type":"rich_text","content":"..."}]'>{{ old('sections') }}</textarea>
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
                        <div>
                            <label for="seo_image">SEO image URL</label>
                            <input type="text" id="seo_image" name="seo_image" value="{{ old('seo_image') }}">
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="auto-generated">
                    </div>
                    <div>
                        <label for="template">Template</label>
                        <select id="template" name="template">
                            @foreach ($templates as $template)
                                <option value="{{ $template }}" @selected(old('template') === $template)>{{ ucfirst($template) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'draft') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="published_at">Publish at</label>
                        <input type="datetime-local" id="published_at" name="published_at" value="{{ old('published_at') }}">
                    </div>
                    <div>
                        <label for="featured_image">Featured image</label>
                        <input type="text" id="featured_image" name="featured_image" value="{{ old('featured_image') }}" placeholder="URL or upload below">
                        <input type="file" name="featured_image_file" accept="image/*" class="mt-2 block w-full text-sm">
                        @error('featured_image_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full">Create Page</button>
            </aside>
        </div>
    </form>
@endsection
