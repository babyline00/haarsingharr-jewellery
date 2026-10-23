@extends('admin.layout')

@section('title', 'Edit Product — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Edit: {{ $product->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('products.show', $product) }}" target="_blank" class="btn-outline text-sm">View on store →</a>
            <a href="{{ route('admin.products.index') }}" class="btn-ghost text-sm">Back to list</a>
        </div>
    </div>

    <form action="{{ route('admin.products.update', $product) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="grid lg:grid-cols-[1fr_320px] gap-6">

            {{-- Main column --}}
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Basic Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name">Product name</label>
                            <input type="text" id="name" name="name" required value="{{ old('name', $product->name) }}">
                            @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="short_description">Short description</label>
                            <input type="text" id="short_description" name="short_description" value="{{ old('short_description', $product->short_description) }}">
                        </div>
                        <div>
                            <label for="description">Detailed description</label>
                            <textarea id="description" name="description" rows="6">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Pricing</h2>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div>
                            <label for="regular_price">Regular price (Rs.) *</label>
                            <input type="number" id="regular_price" name="regular_price" step="0.01" required value="{{ old('regular_price', $product->regular_price) }}">
                            @error('regular_price') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="sale_price">Sale price (Rs.)</label>
                            <input type="number" id="sale_price" name="sale_price" step="0.01" value="{{ old('sale_price', $product->sale_price) }}">
                            @error('sale_price') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        @can('products.adjust_cost')
                        <div>
                            <label for="cost_price">Cost price (Rs.) — restricted</label>
                            <input type="number" id="cost_price" name="cost_price" step="0.01" value="{{ old('cost_price', $product->cost_price) }}">
                            @error('cost_price') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        @endcan
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="sale_starts_at">Sale starts at</label>
                            <input type="datetime-local" id="sale_starts_at" name="sale_starts_at" value="{{ old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div>
                            <label for="sale_ends_at">Sale ends at</label>
                            <input type="datetime-local" id="sale_ends_at" name="sale_ends_at" value="{{ old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Properties</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="metal">Metal</label>
                            <input type="text" id="metal" name="metal" value="{{ old('metal', $product->metal) }}">
                        </div>
                        <div>
                            <label for="gemstone">Gemstone</label>
                            <input type="text" id="gemstone" name="gemstone" value="{{ old('gemstone', $product->gemstone) }}">
                        </div>
                        <div>
                            <label for="carat">Carat</label>
                            <input type="number" id="carat" name="carat" step="0.001" value="{{ old('carat', $product->carat) }}">
                        </div>
                        <div>
                            <label for="weight_grams">Weight (grams)</label>
                            <input type="number" id="weight_grams" name="weight_grams" step="0.01" value="{{ old('weight_grams', $product->weight_grams) }}">
                        </div>
                        <div>
                            <label for="dimensions">Dimensions</label>
                            <input type="text" id="dimensions" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}">
                        </div>
                        <div>
                            <label for="material">Material</label>
                            <input type="text" id="material" name="material" value="{{ old('material', $product->material) }}">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="care_instructions">Care instructions</label>
                        <textarea id="care_instructions" name="care_instructions" rows="3">{{ old('care_instructions', $product->care_instructions) }}</textarea>
                    </div>
                </div>

                {{-- Images --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-serif text-lg">Images</h2>
                    </div>

                    @if ($product->images->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-4">
                            @foreach ($product->images as $img)
                                <div class="relative group border border-charcoal/10 rounded-lg overflow-hidden bg-ivory-alt">
                                    <img src="{{ \App\Support\Media::url($img->path) }}" alt="{{ $img->alt_text }}" class="w-full aspect-square object-cover">
                                    <div class="absolute top-1 left-1 flex gap-1">
                                        @if ($img->is_primary)
                                            <span class="badge bg-champagne text-charcoal text-[10px]">Primary</span>
                                        @endif
                                    </div>
                                    <div class="absolute bottom-0 inset-x-0 bg-charcoal/70 text-ivory text-xs p-1.5 flex items-center justify-between opacity-0 group-hover:opacity-100 transition">
                                        @unless ($img->is_primary)
                                            <form action="{{ route('admin.products.images.primary', [$product, $img]) }}" method="post" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="hover:text-champagne">Primary</button>
                                            </form>
                                        @endunless
                                        <form action="{{ route('admin.products.images.destroy', [$product, $img]) }}" method="post" class="inline" onsubmit="return confirm('Remove this image?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="hover:text-danger">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted mb-4">No images uploaded yet.</p>
                    @endif

                    <form action="{{ route('admin.products.images.store', $product) }}" method="post" enctype="multipart/form-data" class="space-y-3 border-t border-charcoal/5 pt-4" id="product-image-form">
                        @csrf
                        <div>
                            <label for="images">Upload images</label>
                            <input type="file" id="images" name="images[]" multiple accept="image/*" required>
                            @error('images') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            @error('images.*') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>

                        {{-- Client-side file size validation & compression --}}
                        @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const form = document.getElementById('product-image-form');
                                if (!form) return;
                                const fileInput = form.querySelector('input[name="images[]"]');
                                if (!fileInput) return;
                                
                                fileInput.addEventListener('change', async function() {
                                    if (this.files.length === 0) return;
                                    
                                    const compressed = [];
                                    let needsCompression = false;
                                    
                                    for (let i = 0; i < this.files.length; i++) {
                                        if (this.files[i].size > 5 * 1024 * 1024) {
                                            needsCompression = true;
                                        }
                                    }
                                    
                                    if (needsCompression) {
                                        try {
                                            const results = await ImageCompressor.compressAll(Array.from(this.files), { maxSizeMB: 8 });
                                            const dt = new DataTransfer();
                                            results.forEach(f => dt.items.add(f));
                                            this.files = dt.files;
                                            console.log('Compressed ' + results.length + ' files');
                                        } catch (e) {
                                            console.warn('Compression failed:', e);
                                        }
                                    }
                                    
                                    // Final size check
                                    let tooLarge = false;
                                    for (let i = 0; i < this.files.length; i++) {
                                        if (this.files[i].size > 5 * 1024 * 1024) {
                                            tooLarge = true;
                                            break;
                                        }
                                    }
                                    if (tooLarge) {
                                        alert('One or more files are still too large. Server limit is 2MB. Please choose smaller files or use URL-based upload.');
                                        this.value = '';
                                    }
                                });
                                
                                form.addEventListener('submit', function(e) {
                                    let tooLarge = false;
                                    for (let i = 0; i < fileInput.files.length; i++) {
                                        if (fileInput.files[i].size > 5 * 1024 * 1024) {
                                            tooLarge = true;
                                            break;
                                        }
                                    }
                                    if (tooLarge) {
                                        e.preventDefault();
                                        alert('One or more files exceed 5MB. Please choose smaller files.');
                                    }
                                });
                            });
                        </script>
                        @endpush
                        </div>
                        <div>
                            <label for="alt_text">Alt text (optional, applied to all)</label>
                            <input type="text" id="alt_text" name="alt_text" value="{{ old('alt_text') }}">
                        </div>
                        <button type="submit" class="btn-gold text-sm">Upload</button>
                    </form>
                    @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.getElementById('product-image-form');
                            if (!form) return;
                            const fileInput = form.querySelector('input[name="images[]"]');
                            if (!fileInput) return;
                            
                            fileInput.addEventListener('change', function() {
                                let tooLarge = false;
                                for (let i = 0; i < this.files.length; i++) {
                                    if (this.files[i].size > 5 * 1024 * 1024) {
                                        tooLarge = true;
                                        break;
                                    }
                                }
                                if (tooLarge) {
                                    alert('One or more files exceed 5MB. Please choose smaller files.');
                                    this.value = '';
                                }
                            });
                            
                            form.addEventListener('submit', function(e) {
                                let tooLarge = false;
                                for (let i = 0; i < fileInput.files.length; i++) {
                                    if (fileInput.files[i].size > 5 * 1024 * 1024) {
                                        tooLarge = true;
                                        break;
                                    }
                                }
                                if (tooLarge) {
                                    e.preventDefault();
                                    alert('One or more files exceed 5MB. Please choose smaller files.');
                                }
                            });
                        });
                    </script>
                    @endpush
                </div>

                {{-- Variants --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-serif text-lg">Variants</h2>
                        @can('products.update')
                        <button type="button" @click="$dispatch('open-variant-modal')" class="btn-outline text-sm">+ Add Variant</button>
                        @endcan
                    </div>

                    @if ($product->variants->isEmpty())
                        <p class="text-sm text-muted">This product has no variants. Add one for size/colour options.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                                    <tr>
                                        <th class="text-left p-2">SKU</th>
                                        <th class="text-left p-2">Title</th>
                                        <th class="text-right p-2">Price</th>
                                        <th class="text-right p-2">Stock</th>
                                        <th class="text-left p-2">Default?</th>
                                        <th class="text-right p-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-charcoal/5">
                                    @foreach ($product->variants as $variant)
                                        <tr>
                                            <td class="p-2 font-mono text-xs">{{ $variant->sku ?? '—' }}</td>
                                            <td class="p-2">{{ $variant->title ?? $variant->formattedAttributes() }}</td>
                                            <td class="p-2 text-right">Rs. {{ number_format($variant->currentPrice()) }}</td>
                                            <td class="p-2 text-right">{{ $variant->availableStock() }}</td>
                                            <td class="p-2">
                                                @if ($variant->is_default) <span class="badge bg-champagne/30 text-charcoal">Default</span> @endif
                                            </td>
                                            <td class="p-2 text-right">
                                                <a href="{{ route('admin.inventory.index', ['variant' => $variant->id]) }}" class="text-xs text-champagne-dark hover:underline">Inventory</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-4">
                <div class="card p-5">
                    <h3 class="font-serif text-base mb-3">Status & Visibility</h3>
                    <div class="space-y-3">
                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($product->status->value === $status->value)>{{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_featured" value="1" @checked($product->is_featured)> Featured
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_best_seller" value="1" @checked($product->is_best_seller)> Bestseller
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_new_arrival" value="1" @checked($product->is_new_arrival)> New Arrival
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_bridal" value="1" @checked($product->is_bridal)> Bridal
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_men" value="1" @checked($product->is_men)> For Men
                        </label>
                    </div>
                </div>

                <div class="card p-5">
                    <h3 class="font-serif text-base mb-3">Organisation</h3>
                    <div class="space-y-3">
                        <div>
                            <label for="category_id">Category</label>
                            <select id="category_id" name="category_id">
                                <option value="">—</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected((int) $product->category_id === $cat->id)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="sku">SKU</label>
                            <input type="text" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                        </div>
                        <div>
                            <label for="barcode">Barcode</label>
                            <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}">
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h3 class="font-serif text-base mb-3">SEO</h3>
                    <div class="space-y-3">
                        <div>
                            <label for="seo_title">SEO title</label>
                            <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $product->seo_title) }}">
                        </div>
                        <div>
                            <label for="seo_description">Meta description</label>
                            <textarea id="seo_description" name="seo_description" rows="3">{{ old('seo_description', $product->seo_description) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-gold flex-1">Save Changes</button>
                    <a href="{{ route('admin.products.index') }}" class="btn-ghost">Cancel</a>
                </div>
            </aside>
        </div>
    </form>
@endsection
