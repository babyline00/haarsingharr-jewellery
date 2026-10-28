@extends('admin.layout')

@section('title', 'New Product — Haarsingharr Admin')

@section('content')
    <x-admin.header title="New product" subtitle="Add the key details, price and product photos. You can edit every field later.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.products.index')" variant="outline" size="sm" icon="arrow-left">All products</x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    <form action="{{ route('admin.products.store') }}" method="post" enctype="multipart/form-data" id="product-create-form">
        @csrf
        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-5">
                <section class="card p-5 sm:p-6">
                    <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-champagne-dark">Step 1 · Product details</p>
                    <h2 class="mb-4 mt-1 font-serif text-xl">What are you selling?</h2>
                    <div class="space-y-4">
                        <x-admin.field name="name" label="Product name" :required="true" hint="This is shown to customers and used to create the product URL.">
                            <x-admin.input name="name" :value="old('name')" required autofocus placeholder="e.g. Pearl Drop Earrings" />
                        </x-admin.field>
                        <x-admin.field name="short_description" label="Short description" hint="One sentence for product cards.">
                            <x-admin.input name="short_description" :value="old('short_description')" placeholder="Handcrafted gold with freshwater pearls" />
                        </x-admin.field>
                        <x-admin.field name="description" label="Full description">
                            <x-admin.textarea name="description" :value="old('description')" :rows="5" placeholder="Describe the materials, craftsmanship and fit." />
                        </x-admin.field>
                    </div>
                </section>

                <section class="card p-5 sm:p-6">
                    <div class="mb-4">
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-champagne-dark">Step 2 · Photos</p>
                        <h2 class="mt-1 font-serif text-xl">Show the real product</h2>
                        <p class="mt-1 text-sm text-muted">The first image becomes the cover image used on product cards.</p>
                    </div>
                    <label for="product-create-images">Choose product images <span class="text-xs font-normal text-muted">(optional)</span></label>
                    <input type="file" id="product-create-images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                           class="block w-full rounded-lg border border-dashed border-[#9c744a]/40 bg-[#fffdf9] p-3 text-sm">
                    <p class="mt-2 text-xs text-muted">JPG, PNG, WebP or GIF · Up to 10 images · 5 MB each</p>
                    <x-admin.field name="image_alt_text" label="Shared image description" hint="Applied to the photos for screen readers.">
                        <x-admin.input name="image_alt_text" :value="old('image_alt_text')" placeholder="Gold pearl drop earrings on a cream background" />
                    </x-admin.field>
                    <div id="product-create-image-error" class="mt-2 hidden text-sm text-danger" role="alert" tabindex="-1"></div>
                    <div id="product-create-image-preview" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-live="polite"></div>
                    @error('images') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                    @error('images.*') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                </section>

                <section class="card p-5 sm:p-6">
                    <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-champagne-dark">Step 3 · Price</p>
                    <h2 class="mb-4 mt-1 font-serif text-xl">Set your price</h2>
                    <div class="grid gap-4 sm:grid-cols-2 @can('products.adjust_cost') lg:grid-cols-3 @endcan">
                        <x-admin.field name="regular_price" label="Regular price (Rs.)" :required="true">
                            <x-admin.input name="regular_price" type="number" step="0.01" min="0" :value="old('regular_price')" required />
                        </x-admin.field>
                        <x-admin.field name="sale_price" label="Sale price (optional)" hint="Leave blank if there is no sale.">
                            <x-admin.input name="sale_price" type="number" step="0.01" min="0" :value="old('sale_price')" />
                        </x-admin.field>
                        @can('products.adjust_cost')
                            <x-admin.field name="cost_price" label="Cost price (staff only)">
                                <x-admin.input name="cost_price" type="number" step="0.01" min="0" :value="old('cost_price')" />
                            </x-admin.field>
                        @endcan
                    </div>
                    @error('regular_price') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                    @error('sale_price') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-24">
                <section class="card space-y-4 p-5">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[.2em] text-champagne-dark">Step 4 · Store settings</p>
                        <h2 class="mb-3 mt-1 font-serif text-xl">Where should it appear?</h2>
                        <label for="status">Product status</label>
                        <select id="status" name="status" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(old('status', 'draft') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">Choose Draft while the product is being prepared.</p>
                    </div>

                    <div>
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-admin.field name="sku" label="SKU" hint="Optional internal stock code.">
                        <x-admin.input name="sku" :value="old('sku')" placeholder="e.g. EAR-014" />
                    </x-admin.field>
                    @error('category_id') <p class="text-sm text-danger">{{ $message }}</p> @enderror
                    @error('sku') <p class="text-sm text-danger">{{ $message }}</p> @enderror
                </section>

                <section class="card space-y-3 p-5">
                    <h2 class="font-serif text-lg">Collections</h2>
                    <x-admin.checkbox name="is_featured" label="Feature on the storefront" :checked="old('is_featured')" />
                    <x-admin.checkbox name="is_new_arrival" label="Mark as new arrival" :checked="old('is_new_arrival')" />
                    <x-admin.checkbox name="is_best_seller" label="Mark as bestseller" :checked="old('is_best_seller')" />
                    <x-admin.checkbox name="is_bridal" label="Include in bridal edit" :checked="old('is_bridal')" />
                    <x-admin.checkbox name="has_variants" label="Has sizes or colour options" :checked="old('has_variants')" />
                    <p class="text-xs text-muted">You can change these later and add size or colour variants after saving.</p>
                </section>

                <section class="card space-y-2 p-5">
                    <h2 class="font-serif text-lg">Stock</h2>
                    <p class="text-xs text-muted">
                        A default variant is created automatically with the price above,
                        starting at zero stock. On the next screen, open the
                        <strong>Variants</strong> card to add stock through the
                        inventory ledger — every movement stays auditable.
                    </p>
                </section>

                <div class="flex flex-col gap-2">
                    <button type="submit" class="btn-gold w-full">Create product</button>
                    <a href="{{ route('admin.products.index') }}" class="btn-outline w-full">Cancel</a>
                </div>
            </aside>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('product-create-images');
            const preview = document.getElementById('product-create-image-preview');
            const error = document.getElementById('product-create-image-error');
            const form = document.getElementById('product-create-form');
            if (!input || !preview || !error || !form) return;

            function renderPreviews() {
                preview.replaceChildren();
                error.textContent = '';
                error.classList.add('hidden');
                const files = Array.from(input.files || []);
                if (files.length > 10 || files.some((file) => file.size > 5 * 1024 * 1024)) {
                    error.textContent = files.length > 10 ? 'Choose no more than 10 images.' : 'Each image must be 5 MB or smaller.';
                    error.classList.remove('hidden');
                }

                files.forEach((file, index) => {
                    const tile = document.createElement('div');
                    tile.className = 'overflow-hidden rounded-lg border border-charcoal/10 bg-white';
                    const image = document.createElement('img');
                    image.src = URL.createObjectURL(file);
                    image.alt = 'Preview of ' + file.name;
                    image.className = 'aspect-square w-full object-cover';
                    image.onload = () => URL.revokeObjectURL(image.src);
                    const caption = document.createElement('p');
                    caption.className = 'truncate px-2 py-1.5 text-xs text-muted';
                    caption.textContent = index === 0 ? file.name + ' · Cover' : file.name;
                    tile.append(image, caption);
                    preview.append(tile);
                });
            }

            input.addEventListener('change', renderPreviews);
            form.addEventListener('submit', function (event) {
                if (input.files.length > 10 || Array.from(input.files).some((file) => file.size > 5 * 1024 * 1024)) {
                    event.preventDefault();
                    renderPreviews();
                    error.focus();
                }
            });
        });
    </script>
@endpush
