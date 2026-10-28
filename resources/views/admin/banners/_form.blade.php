@php
    $banner = $banner ?? null;
    $configuration = old('configuration', $banner?->configuration ? json_encode($banner->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
    $desktopPreview = $banner?->image
        ? \App\Support\Media::url($banner->image, \App\Support\Media::WIDE_PLACEHOLDER)
        : asset('images/placeholder-wide.svg');
    $mobilePreview = $banner?->mobile_image
        ? \App\Support\Media::url($banner->mobile_image, \App\Support\Media::WIDE_PLACEHOLDER)
        : asset('images/placeholder-wide.svg');
@endphp

<form action="{{ $action }}" method="post" enctype="multipart/form-data" id="banner-form" class="space-y-6">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-5">
            <section class="card p-5 sm:p-6">
                <div class="mb-5">
                    <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-champagne-dark">Creative</p>
                    <h2 class="mt-1 font-serif text-xl">Banner imagery</h2>
                    <p class="mt-1 text-sm text-muted">Choose a URL or upload an image. Uploading a file replaces the saved URL.</p>
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div class="min-w-0">
                        <label for="image">Desktop image <span class="text-danger">*</span></label>
                        <div class="mb-3 overflow-hidden rounded-lg border border-charcoal/10 bg-ivory-alt">
                            <img id="banner-image-preview" src="{{ $desktopPreview }}" alt="Desktop banner preview"
                                 class="aspect-[16/7] w-full object-cover" loading="lazy">
                        </div>
                        <input type="text" id="image" name="image" value="{{ old('image', $banner?->image) }}"
                               placeholder="/images/campaign.jpg or https://…">
                        <label for="image_file" class="mt-3">Upload desktop image</label>
                        <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="block w-full rounded-lg border border-dashed border-[#9c744a]/40 bg-[#fffdf9] p-2.5 text-sm">
                        <p id="image_file_error" class="mt-1 hidden text-sm text-danger" role="alert"></p>
                        @error('image') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        @error('image_file') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-muted">Recommended: 1920 × 840 px (about 16:7). Homepage banners display in a fixed wide frame and crop with “cover”. JPG, PNG, WebP or GIF · 5 MB maximum.</p>
                    </div>

                    <div class="min-w-0">
                        <label for="mobile_image">Mobile image <span class="text-xs font-normal text-muted">(optional)</span></label>
                        <div class="mb-3 overflow-hidden rounded-lg border border-charcoal/10 bg-ivory-alt">
                            <img id="banner-mobile-preview" src="{{ $mobilePreview }}" alt="Mobile banner preview"
                                 class="aspect-[4/5] w-full object-cover" loading="lazy">
                        </div>
                        <input type="text" id="mobile_image" name="mobile_image" value="{{ old('mobile_image', $banner?->mobile_image) }}"
                               placeholder="/images/campaign-mobile.jpg or https://…">
                        <label for="mobile_image_file" class="mt-3">Upload mobile image</label>
                        <input type="file" id="mobile_image_file" name="mobile_image_file" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="block w-full rounded-lg border border-dashed border-[#9c744a]/40 bg-[#fffdf9] p-2.5 text-sm">
                        <p id="mobile_image_file_error" class="mt-1 hidden text-sm text-danger" role="alert"></p>
                        @error('mobile_image_file') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-muted">Recommended: 900 × 1125 px (4:5 portrait). This image replaces the desktop crop on phones.</p>
                    </div>
                </div>
            </section>

            <section class="card p-5 sm:p-6">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-champagne-dark">Message</p>
                <h2 class="mb-5 mt-1 font-serif text-xl">Banner content</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name">Internal name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="120" value="{{ old('name', $banner?->name) }}" autofocus>
                        @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="heading">Heading</label>
                        <input type="text" id="heading" name="heading" maxlength="200" value="{{ old('heading', $banner?->heading) }}">
                        @error('heading') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="subheading">Subheading</label>
                        <input type="text" id="subheading" name="subheading" maxlength="300" value="{{ old('subheading', $banner?->subheading) }}">
                        @error('subheading') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="link_url">Destination URL</label>
                        <input type="text" id="link_url" name="link_url" maxlength="300" value="{{ old('link_url', $banner?->link_url) }}" placeholder="/shop or https://…">
                        @error('link_url') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="link_text">Button text</label>
                        <input type="text" id="link_text" name="link_text" maxlength="80" value="{{ old('link_text', $banner?->link_text) }}" placeholder="Explore the collection">
                        @error('link_text') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="alt_text">Image description <span class="text-xs font-normal text-muted">(screen readers)</span></label>
                        <input type="text" id="alt_text" name="alt_text" maxlength="300" value="{{ old('alt_text', $banner?->alt_text) }}" placeholder="Describe the visible campaign image">
                        @error('alt_text') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <details class="card p-5 sm:p-6">
                <summary class="cursor-pointer font-serif text-lg">Advanced configuration</summary>
                <p class="mb-3 mt-2 text-sm text-muted">Optional JSON settings for custom placement behavior.</p>
                <label for="configuration">Configuration JSON</label>
                <textarea id="configuration" name="configuration" rows="5" class="font-mono text-xs" placeholder='{"overlay": 0.3, "align": "left"}'>{{ $configuration }}</textarea>
                @error('configuration') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </details>
        </div>

        <aside class="space-y-4">
            <section class="card space-y-4 p-5">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-champagne-dark">Placement</p>
                    <h2 class="mb-3 mt-1 font-serif text-xl">Publish settings</h2>
                    <label for="location">Placement <span class="text-danger">*</span></label>
                    <select id="location" name="location" required>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(old('location', $banner?->location) === $location)>{{ ucwords(str_replace('_', ' ', $location)) }}</option>
                        @endforeach
                    </select>
                    @error('location') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sort_order">Display order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}">
                    @error('sort_order') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="starts_at">Start date</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}">
                    @error('starts_at') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ends_at">End date</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $banner?->is_published ?? true))>
                    Publish banner
                </label>
                <x-admin.error-summary />
            </section>
            <button type="submit" class="btn-gold w-full">{{ $banner ? 'Save banner' : 'Create banner' }}</button>
            <a href="{{ route('admin.banners.index') }}" class="btn-outline w-full">Cancel</a>
        </aside>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pairs = [
                { file: 'image_file', image: 'banner-image-preview', error: 'image_file_error', url: 'image' },
                { file: 'mobile_image_file', image: 'banner-mobile-preview', error: 'mobile_image_file_error', url: 'mobile_image' },
            ];

            pairs.forEach(function (pair) {
                const fileInput = document.getElementById(pair.file);
                const preview = document.getElementById(pair.image);
                const error = document.getElementById(pair.error);
                const urlInput = document.getElementById(pair.url);
                if (!fileInput || !preview || !error || !urlInput) return;

                fileInput.addEventListener('change', function () {
                    error.textContent = '';
                    error.classList.add('hidden');
                    const file = fileInput.files[0];
                    if (!file) return;
                    if (file.size > 5 * 1024 * 1024) {
                        error.textContent = 'Choose an image that is 5 MB or smaller.';
                        error.classList.remove('hidden');
                        fileInput.value = '';
                        return;
                    }
                    preview.src = URL.createObjectURL(file);
                    preview.onload = function () { URL.revokeObjectURL(preview.src); };
                    urlInput.value = '';
                });

                urlInput.addEventListener('input', function () {
                    if (urlInput.value.trim()) preview.src = urlInput.value.trim();
                });
            });

            const form = document.getElementById('banner-form');
            form?.addEventListener('submit', function (event) {
                const oversized = pairs.some(function (pair) {
                    const file = document.getElementById(pair.file)?.files[0];
                    return file && file.size > 5 * 1024 * 1024;
                });
                if (oversized) event.preventDefault();
            });
        });
    </script>
@endpush
