@php
    $banner = $banner ?? null;
    $configuration = old('configuration', $banner?->configuration ? json_encode($banner->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
<<<<<<< HEAD
    $desktopPreview = $banner?->image
        ? \App\Support\Media::url($banner->image, \App\Support\Media::WIDE_PLACEHOLDER)
        : asset('images/placeholder-wide.svg');
    $mobilePreview = $banner?->mobile_image
        ? \App\Support\Media::url($banner->mobile_image, \App\Support\Media::WIDE_PLACEHOLDER)
        : asset('images/placeholder-wide.svg');
@endphp

<form action="{{ $action }}" method="post" enctype="multipart/form-data" id="banner-form" class="space-y-6">
=======
@endphp

<form action="{{ $action }}" method="post" enctype="multipart/form-data">
>>>>>>> origin/main
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

<<<<<<< HEAD
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
=======
    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Banner</h2>
                <div class="space-y-4">
                    <div>
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required value="{{ old('name', $banner?->name) }}" autofocus>
                        @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="image">Image *</label>
                        @if ($banner?->image)
                            <div class="mb-2">
                                <img src="{{ App\Support\Media::url($banner->image) }}" alt="" class="h-24 w-auto rounded border border-charcoal/10 object-cover">
                                <p class="text-xs text-muted mt-1">Current: {{ $banner->image }}</p>
                            </div>
                        @endif
                        <input type="text" id="image" name="image" value="{{ old('image', $banner?->image) }}" placeholder="/storage/banners/hero.jpg (or upload below)">
                        <input type="file" id="image_file" name="image_file" accept="image/*" class="mt-2 block w-full text-sm">
                        <p class="text-xs text-muted mt-1">Upload a new image (jpg/png/webp, max 5MB) — overrides the URL above. Server limit: {{ ini_get('upload_max_filesize') }}</p>
                        @error('image_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    {{-- Client-side file size validation & compression --}}
                    @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.querySelector('form[enctype="multipart/form-data"]');
                            if (!form) return;
                            
                            const fileInput = form.querySelector('input[name="image_file"]');
                            if (!fileInput) return;
                            
                            fileInput.addEventListener('change', async function() {
                                const file = this.files[0];
                                if (!file) return;
                                
                                // Check if file is too large for server
                                if (file.size > 5 * 1024 * 1024) {
                                    // Try to compress
                                    try {
                                        const compressed = await ImageCompressor.compress(file, { maxSizeMB: 4 });
                                        if (compressed.size < file.size) {
                                            // Replace the file in the input
                                            const dt = new DataTransfer();
                                            dt.items.add(compressed);
                                            this.files = dt.files;
                                            console.log('Compressed from ' + (file.size/1024).toFixed(0) + 'KB to ' + (compressed.size/1024).toFixed(0) + 'KB');
                                        }
                                    } catch (e) {
                                        console.warn('Compression failed:', e);
                                    }
                                }
                                
                                // Final size check
                                const finalFile = this.files[0];
                                if (finalFile && finalFile.size > 5 * 1024 * 1024) {
                                    alert('File is still too large (' + (finalFile.size/1024/1024).toFixed(1) + 'MB). Server limit is {{ ini_get('upload_max_filesize') }}. Please choose a smaller file or use the URL field instead.');
                                    this.value = '';
                                }
                            });
                            
                            form.addEventListener('submit', function(e) {
                                const file = fileInput.files[0];
                                if (file && file.size > 5 * 1024 * 1024) {
                                    e.preventDefault();
                                    alert('File size exceeds 5MB. Please choose a smaller file or use the URL field instead.');
                                }
                            });
                        });
                    </script>
                    @endpush
                    </div>

                    {{-- Client-side file size validation --}}
                    @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.querySelector('form[enctype="multipart/form-data"]');
                            if (!form) return;
                            
                            const fileInput = form.querySelector('input[name="image_file"]');
                            if (!fileInput) return;
                            
                            fileInput.addEventListener('change', function() {
                                const file = this.files[0];
                                if (file && file.size > 5 * 1024 * 1024) {
                                    alert('File size exceeds 5MB. Please choose a smaller file or use the URL field instead.');
                                    this.value = '';
                                }
                            });
                            
                            form.addEventListener('submit', function(e) {
                                const file = fileInput.files[0];
                                if (file && file.size > 5 * 1024 * 1024) {
                                    e.preventDefault();
                                    alert('File size exceeds 5MB. Please choose a smaller file.');
                                }
                            });
                        });
                    </script>
                    @endpush
                    </div>
                    <div>
                        <label for="mobile_image">Mobile image</label>
                        @if ($banner?->mobile_image)
                            <div class="mb-2">
                                <img src="{{ \App\Support\Media::url($banner->mobile_image) }}" alt="" class="h-16 w-auto rounded border border-charcoal/10 object-cover">
                            </div>
                        @endif
                        <input type="text" id="mobile_image" name="mobile_image" value="{{ old('mobile_image', $banner?->mobile_image) }}">
                        <input type="file" id="mobile_image_file" name="mobile_image_file" accept="image/*" class="mt-2 block w-full text-sm">
                        @error('mobile_image_file') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label for="heading">Heading</label>
                            <input type="text" id="heading" name="heading" value="{{ old('heading', $banner?->heading) }}">
                        </div>
                        <div>
                            <label for="subheading">Subheading</label>
                            <input type="text" id="subheading" name="subheading" value="{{ old('subheading', $banner?->subheading) }}">
                        </div>
                        <div>
                            <label for="link_url">Link URL</label>
                            <input type="text" id="link_url" name="link_url" value="{{ old('link_url', $banner?->link_url) }}">
                        </div>
                        <div>
                            <label for="link_text">Link text</label>
                            <input type="text" id="link_text" name="link_text" value="{{ old('link_text', $banner?->link_text) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-1">Configuration</h2>
                <p class="text-xs text-muted mb-3">Optional JSON payload (overlay colour, alignment, etc.).</p>
                <textarea id="configuration" name="configuration" rows="5" class="font-mono text-xs" placeholder='{"overlay": 0.3, "align": "left"}'>{{ $configuration }}</textarea>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5 space-y-4">
                <div>
                    <label for="location">Location *</label>
>>>>>>> origin/main
                    <select id="location" name="location" required>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(old('location', $banner?->location) === $location)>{{ ucwords(str_replace('_', ' ', $location)) }}</option>
                        @endforeach
                    </select>
<<<<<<< HEAD
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
=======
                    @error('location') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sort_order">Sort order</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}">
                </div>
                <div>
                    <label for="starts_at">Starts at</label>
                    <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}">
                </div>
                <div>
                    <label for="ends_at">Ends at</label>
                    <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $banner?->is_published ?? true))>
                    Published
                </label>
            </div>

            <button type="submit" class="btn-gold w-full">{{ $banner ? 'Save Changes' : 'Create Banner' }}</button>
        </aside>
    </div>
</form>
>>>>>>> origin/main
