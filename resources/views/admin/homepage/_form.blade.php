@php
    $section = $section ?? null;
    $configuration = old('configuration', $section?->configuration ? json_encode($section->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
@endphp

<form action="{{ $action }}" method="post">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Content</h2>
                <div class="space-y-4">
                    <div>
                        <label for="key">Key *</label>
                        <input type="text" id="key" name="key" required value="{{ old('key', $section?->key) }}" placeholder="e.g. hero, featured_collection" autofocus>
                        <p class="text-xs text-muted mt-1">A unique machine name used by the storefront to render this section.</p>
                        @error('key') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="{{ old('title', $section?->title) }}">
                    </div>
                    <div>
                        <label for="subtitle">Subtitle</label>
                        <input type="text" id="subtitle" name="subtitle" value="{{ old('subtitle', $section?->subtitle) }}">
                    </div>
                    <div>
                        <label for="content">Content (HTML allowed)</label>
<<<<<<< HEAD
                        <textarea id="content" name="content" rows="8" class="font-mono text-xs">{{ old('content', $section?->content) }}</textarea>
=======
                        <textarea id="content" name="content" rows="8" class="ckeditor font-mono text-xs">{{ old('content', $section?->content) }}</textarea>
>>>>>>> origin/main
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-1">Configuration</h2>
                <p class="text-xs text-muted mb-3">Optional JSON payload consumed by the section view.</p>
<<<<<<< HEAD
                <p class="mb-3 rounded-lg bg-ivory-alt p-3 text-xs text-charcoal-soft">For a homepage Reels section, set the key to <code>trending_reels</code> and add an <code>items</code> list. Each item needs a hosted MP4 URL; poster, title and views are optional. Publish or unpublish this section to show or hide it.</p>
                <textarea id="configuration" name="configuration" rows="8" class="font-mono text-xs" placeholder='{"items":[{"video":"https://cdn.example.com/reel.mp4","poster":"/storage/reel-cover.jpg","title":"Bridal details","views":"1.2k"}]}'>{{ $configuration }}</textarea>
=======
                <textarea id="configuration" name="configuration" rows="6" class="font-mono text-xs" placeholder='{"limit": 8, "background": "#fff"}'>{{ $configuration }}</textarea>
>>>>>>> origin/main
                @error('configuration') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="view">Storefront view</label>
                    <input type="text" id="view" name="view" value="{{ old('view', $section?->view) }}" placeholder="homepage.sections.hero">
                </div>
                <div>
                    <label for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $section?->sort_order ?? 0) }}">
                </div>
                <div>
                    <label for="starts_at">Starts at</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $section?->starts_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label for="ends_at">Ends at</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $section?->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $section?->is_published ?? true))>
                    Published
                </label>
            </div>

            <button type="submit" class="btn-gold w-full">{{ $section ? 'Save Changes' : 'Create Section' }}</button>
        </aside>
    </div>
</form>
