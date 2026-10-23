@php
    $banner = $banner ?? null;
    $configuration = old('configuration', $banner?->configuration ? json_encode($banner->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '');
@endphp

<form action="{{ $action }}" method="post" enctype="multipart/form-data">
    @csrf
    @if (($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

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
                                        const compressed = await ImageCompressor.compress(file, { maxSizeMB: 8 });
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
                                    alert('File is still too large (' + (finalFile.size/1024/1024).toFixed(1) + 'MB). Server limit is 2MB. Please choose a smaller file or use the URL field instead.');
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
                    <select id="location" name="location" required>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(old('location', $banner?->location) === $location)>{{ ucwords(str_replace('_', ' ', $location)) }}</option>
                        @endforeach
                    </select>
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
