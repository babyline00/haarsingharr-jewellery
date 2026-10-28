@extends('admin.layout')

@section('title', 'Edit Product — Haarsingharr Admin')

@section('content')
    <div class="pb-24">
    @php
        $statusOptions = collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all();
        $categoryOptions = $categories->pluck('name', 'id')->all();
        $seoTitle = old('seo_title', $product->seo_title);
        $seoDescription = old('seo_description', $product->seo_description);
    @endphp

    <x-admin.header :title="'Edit: '.$product->name"
                    subtitle="Update content, pricing, variants, images and search metadata.">
        <x-slot:actions>
            <x-admin.button :href="route('products.show', $product->slug)" target="_blank" rel="noopener"
                            variant="outline" size="sm" icon="external-link">
                Preview
            </x-admin.button>

            @can('products.create')
                <form action="{{ route('admin.products.duplicate', $product) }}" method="post">
                    @csrf
                    <x-admin.button type="submit" variant="outline" size="sm" icon="save">Duplicate</x-admin.button>
                </form>
            @endcan

            <x-admin.button :href="route('admin.products.index')" variant="ghost" size="sm" icon="arrow-left">
                Back to products
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    <nav aria-label="Product sections" class="sticky top-16 z-20 mb-5 flex gap-1 overflow-x-auto rounded-xl border border-charcoal/10 bg-porcelain/95 p-1.5 shadow-sm backdrop-blur">
        @foreach ([
            ['Details', 'product-information'], ['Price', 'product-pricing'], ['Properties', 'product-properties'],
            ['Visibility', 'product-visibility'], ['Photos', 'product-images'], ['Variants', 'product-variants'],
            ['Recommendations', 'product-recommendations'], ['Activity', 'product-activity'],
        ] as [$label, $target])
            <a href="#{{ $target }}" class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium text-charcoal-soft transition hover:bg-ivory-alt hover:text-charcoal">{{ $label }}</a>
        @endforeach
    </nav>

    <form id="product-edit-form" action="{{ route('admin.products.update', $product) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        <div class="grid lg:grid-cols-[1fr_330px] gap-6 items-start">
            <div class="space-y-5">
                <x-admin.card id="product-information" title="Basic information" class="scroll-mt-32">
                    <div class="space-y-4">
<<<<<<< HEAD
                        <x-admin.field name="name" label="Product name" :required="true">
                            <x-admin.input name="name" :value="$product->name" required />
                        </x-admin.field>
=======
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
                            <textarea id="description" name="description" rows="6" class="ckeditor">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>
>>>>>>> origin/main

                        <x-admin.field name="short_description" label="Short description"
                                       hint="One line shown in listings and search results.">
                            <x-admin.input name="short_description" :value="$product->short_description" />
                        </x-admin.field>

                        <x-admin.field name="description" label="Detailed description">
                            <x-admin.textarea name="description" :value="$product->description" :rows="8" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <x-admin.card id="product-pricing" title="Pricing" subtitle="Sale price must be lower than the regular price." class="scroll-mt-32">
                    <div class="grid sm:grid-cols-3 gap-4">
                        <x-admin.field name="regular_price" label="Regular price (Rs.)" :required="true">
                            <x-admin.input name="regular_price" type="number" step="0.01" :value="$product->regular_price" required />
                        </x-admin.field>

                        <x-admin.field name="sale_price" label="Sale price (Rs.)">
                            <x-admin.input name="sale_price" type="number" step="0.01" :value="$product->sale_price" />
                        </x-admin.field>

                        @can('products.adjust_cost')
                            <x-admin.field name="cost_price" label="Cost price (Rs.)" hint="Restricted to authorised staff.">
                                <x-admin.input name="cost_price" type="number" step="0.01" :value="$product->cost_price" />
                            </x-admin.field>
                        @endcan
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 mt-4">
<<<<<<< HEAD
                        <x-admin.field name="sale_starts_at" label="Sale starts at">
                            <x-admin.input name="sale_starts_at" type="datetime-local"
                                           :value="old('sale_starts_at', optional($product->sale_starts_at)->format('Y-m-d\TH:i'))" />
                        </x-admin.field>
=======
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
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-serif text-lg">Inventory</h2>
                        <a href="{{ route('admin.inventory.index') }}" class="text-xs text-champagne-dark hover:underline">Open inventory →</a>
                    </div>

                    @php
                        $inventoryVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
                        $inventoryAvailable = $product->availableStock();
                        $inventoryThreshold = $inventoryVariant?->low_stock_threshold ?? 5;
                    @endphp

                    <div class="grid sm:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs uppercase tracking-widest text-muted">Available</p>
                            <p class="text-2xl font-serif">{{ $inventoryAvailable }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-widest text-muted">Variants</p>
                            <p class="text-2xl font-serif">{{ $product->variants->count() }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-widest text-muted">Status</p>
                            @if ($inventoryAvailable <= 0)
                                <span class="badge bg-rose-100 text-rose-800">Out of stock</span>
                            @elseif ($inventoryVariant && $inventoryVariant->stock_quantity <= $inventoryThreshold)
                                <span class="badge bg-amber-100 text-amber-800">Low stock</span>
                            @else
                                <span class="badge bg-emerald-100 text-emerald-800">In stock</span>
                            @endif
                        </div>
                    </div>

                    @if ($product->variants->count() <= 1)
                        @can('inventory.update')
                        <div class="grid sm:grid-cols-3 gap-4 border-t border-charcoal/5 pt-4">
                            <div>
                                <label for="stock_quantity">Stock on hand</label>
                                <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', $inventoryVariant?->stock_quantity ?? 0) }}">
                                @error('stock_quantity') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="low_stock_threshold">Low stock threshold</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" step="1" value="{{ old('low_stock_threshold', $inventoryThreshold) }}">
                                @error('low_stock_threshold') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" id="allow_backorder" name="allow_backorder" value="1" @checked(old('allow_backorder', $inventoryVariant?->allow_backorder))> Allow backorders
                                </label>
                            </div>
                        </div>
                        @endcan
                    @else
                        <p class="text-xs text-muted border-t border-charcoal/5 pt-4">This product has multiple variants. Adjust stock per variant on the <a href="{{ route('admin.inventory.index') }}" class="text-champagne-dark hover:underline">Inventory screen</a>.</p>
                    @endif
                </div>
>>>>>>> origin/main

                        <x-admin.field name="sale_ends_at" label="Sale ends at">
                            <x-admin.input name="sale_ends_at" type="datetime-local"
                                           :value="old('sale_ends_at', optional($product->sale_ends_at)->format('Y-m-d\TH:i'))" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <x-admin.card id="product-properties" title="Product properties" class="scroll-mt-32">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-admin.field name="metal" label="Metal">
                            <x-admin.input name="metal" :value="$product->metal" />
                        </x-admin.field>
                        <x-admin.field name="gemstone" label="Gemstone">
                            <x-admin.input name="gemstone" :value="$product->gemstone" />
                        </x-admin.field>
                        <x-admin.field name="carat" label="Carat">
                            <x-admin.input name="carat" type="number" step="0.001" :value="$product->carat" />
                        </x-admin.field>
                        <x-admin.field name="weight_grams" label="Weight (grams)">
                            <x-admin.input name="weight_grams" type="number" step="0.01" :value="$product->weight_grams" />
                        </x-admin.field>
                        <x-admin.field name="size" label="Size">
                            <x-admin.input name="size" :value="$product->size" />
                        </x-admin.field>
                        <x-admin.field name="colour" label="Colour">
                            <x-admin.input name="colour" :value="$product->colour" />
                        </x-admin.field>
                        <x-admin.field name="dimensions" label="Dimensions">
                            <x-admin.input name="dimensions" :value="$product->dimensions" />
                        </x-admin.field>
                        <x-admin.field name="material" label="Material">
                            <x-admin.input name="material" :value="$product->material" />
                        </x-admin.field>
                    </div>

                    <div class="mt-4">
<<<<<<< HEAD
                        <x-admin.field name="care_instructions" label="Care instructions">
                            <x-admin.textarea name="care_instructions" :value="$product->care_instructions" :rows="3" />
                        </x-admin.field>
                    </div>
                </x-admin.card>
=======
                        <label for="care_instructions">Care instructions</label>
                        <textarea id="care_instructions" name="care_instructions" rows="3" class="ckeditor">{{ old('care_instructions', $product->care_instructions) }}</textarea>
                    </div>
                </div>

>>>>>>> origin/main
            </div>

            <aside class="space-y-5">
                <x-admin.card id="product-visibility" title="Status & visibility" class="scroll-mt-32">
                    <div class="space-y-4">
                        <x-admin.field name="status" label="Status" :required="true">
                            <x-admin.select name="status" :selected="$product->status->value" :options="$statusOptions" />
                        </x-admin.field>

                        <div class="space-y-2.5 pt-1">
                            <x-admin.checkbox name="is_featured" label="Featured product" :checked="$product->is_featured" />
                            <x-admin.checkbox name="is_best_seller" label="Bestseller" :checked="$product->is_best_seller" />
                            <x-admin.checkbox name="is_new_arrival" label="New arrival" :checked="$product->is_new_arrival" />
                            <x-admin.checkbox name="is_bridal" label="Bridal edit" :checked="$product->is_bridal" />
                            <x-admin.checkbox name="is_men" label="For men" :checked="$product->is_men" />
                            <div class="pt-2 mt-2 border-t border-charcoal/5 space-y-2.5">
                                <x-admin.checkbox name="allow_reviews" label="Allow customer reviews" :checked="$product->allow_reviews" />
                                <x-admin.checkbox name="allow_backorders" label="Allow backorders" :checked="$product->allow_backorders" />
                            </div>
                        </div>
                    </div>
                </x-admin.card>

                <x-admin.card title="Organisation">
                    <div class="space-y-4">
                        <x-admin.field name="category_id" label="Category">
                            <x-admin.select name="category_id" :selected="$product->category_id" :options="$categoryOptions"
                                            placeholder="No category" />
                        </x-admin.field>

                        <x-admin.field name="sku" label="SKU" hint="Must be unique across the catalogue.">
                            <x-admin.input name="sku" :value="$product->sku" />
                        </x-admin.field>

                        <x-admin.field name="barcode" label="Barcode">
                            <x-admin.input name="barcode" :value="$product->barcode" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <x-admin.card title="SEO" subtitle="Overrides the storefront defaults.">
                    <div class="space-y-4" x-data="{ title: @js($seoTitle), description: @js($seoDescription) }">
                        <x-admin.field name="seo_title" label="SEO title" hint="Aim for 50–60 characters.">
                            <x-admin.input name="seo_title" :value="$seoTitle" x-model="title" />
                            <p class="text-xs text-muted mt-1"><span x-text="(title || '').length"></span>/60 characters</p>
                        </x-admin.field>

                        <x-admin.field name="seo_description" label="Meta description" hint="Aim for 150–160 characters.">
                            <x-admin.textarea name="seo_description" :value="$seoDescription" :rows="3" x-model="description" />
                            <p class="text-xs text-muted mt-1"><span x-text="(description || '').length"></span>/160 characters</p>
                        </x-admin.field>

                        <x-admin.field name="seo_image" label="Social share image URL">
                            <x-admin.input name="seo_image" :value="$product->seo_image" placeholder="/storage/..." />
                        </x-admin.field>

                        <x-admin.field name="canonical_url" label="Canonical URL" hint="Optional. Use when this product is syndicated.">
                            <x-admin.input name="canonical_url" :value="$product->canonical_url" />
                        </x-admin.field>

                        <div class="rounded-lg border border-charcoal/10 p-3 bg-ivory-alt/20">
                            <p class="text-xs uppercase tracking-widest text-muted mb-1">Search preview</p>
                            <p class="text-xs text-success truncate">haarsingharr.pk › products › {{ $product->slug }}</p>
                            <p class="text-sm text-info font-medium truncate" x-text="title || @js($product->name)"></p>
                            <p class="text-xs text-charcoal-soft line-clamp-2" x-text="description || @js(\Illuminate\Support\Str::limit($product->short_description ?: $product->description, 155))"></p>
                        </div>
                    </div>
                </x-admin.card>

            </aside>
        </div>
    </form>

<<<<<<< HEAD
    {{-- Images ------------------------------------------------------------- --}}
    <x-admin.card id="product-images" title="Photos" subtitle="The first image is used in product listings and social shares." :padding="false" class="mt-6 scroll-mt-32">
        <x-slot:actions>
            @can('products.update')
                <x-admin.button type="button" variant="outline" size="sm" icon="upload"
                                x-on:click="$dispatch('open-modal-upload-images')">
                    Upload images
                </x-admin.button>
            @endcan
        </x-slot:actions>

        <div class="p-5">
            @if ($product->images->isEmpty())
                <x-admin.empty icon="image" title="No images yet"
                               description="Upload at least one image so the product can appear on the storefront." />
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3">
                    @foreach ($product->images as $image)
                        <div class="border border-charcoal/10 rounded-lg overflow-hidden bg-ivory-alt">
                            <div class="relative aspect-square">
                                <a href="{{ \App\Support\Media::url($image->path) }}" target="_blank" rel="noopener"
                                   class="block h-full w-full" aria-label="View full image: {{ $image->alt_text ?: $product->name }}">
                                    <img src="{{ \App\Support\Media::url($image->path) }}" alt="{{ $image->alt_text ?: $product->name }}"
                                         class="w-full h-full object-cover" loading="lazy" decoding="async">
                                </a>

                                @if ($image->is_primary)
                                    <span class="absolute top-1.5 left-1.5 badge badge-champagne badge-sm">Primary</span>
                                @endif
                            </div>

                            <div class="p-2 flex items-center justify-between gap-2 bg-porcelain">
                                @unless ($image->is_primary)
                                    <form id="image-primary-{{ $image->id }}"
                                          action="{{ route('admin.products.images.primary', [$product, $image]) }}"
                                          method="post">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <button type="submit" form="image-primary-{{ $image->id }}"
                                            class="text-xs font-medium text-champagne-dark hover:underline">
                                        Make primary
                                    </button>
                                @else
                                    <span class="text-xs text-muted">Primary</span>
                                @endunless

                                @can('products.update')
                                    <x-admin.confirm :action="route('admin.products.images.destroy', [$product, $image])"
                                                     method="DELETE"
                                                     title="Remove this image?"
                                                     message="The file is deleted from storage. This cannot be undone."
                                                     label="Remove"
                                                     confirm-label="Remove image" />
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-admin.card>

    {{-- Variants ----------------------------------------------------------- --}}
    <x-admin.card id="product-variants" title="Variants" subtitle="Sizes, colours and other options with their own SKU, price and stock."
                  :padding="false" class="mt-6">
        <x-slot:actions>
            @can('products.create')
                <x-admin.button type="button" variant="outline" size="sm" icon="grid"
                                x-on:click="$dispatch('open-modal-generate-variants')">
                    Generate matrix
                </x-admin.button>
                <x-admin.button type="button" size="sm" icon="plus"
                                x-on:click="$dispatch('open-modal-add-variant')">
                    Add variant
                </x-admin.button>
            @endcan
        </x-slot:actions>

        @if ($product->variants->isEmpty())
            <div class="p-5">
                <x-admin.empty icon="grid" title="No variants yet"
                               description="Add a single variant, or generate a size × colour matrix in one step." />
            </div>
        @else
            <x-admin.table caption="Product variants">
                <thead>
                    <tr>
                        <x-admin.th label="SKU" />
                        <x-admin.th label="Variant" />
                        <x-admin.th label="Price" align="right" />
                        <x-admin.th label="Stock" align="right" />
                        <x-admin.th label="Default" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($product->variants as $variant)
                        @php
                            $variantStock = $variant->availableStock();
                            $variantLow = $variant->low_stock_threshold !== null
                                && $variantStock <= $variant->low_stock_threshold;
                            $variantStatus = $variant->status ?? 'active';
                        @endphp
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs">{{ $variant->sku ?? '—' }}</td>
                            <td class="p-3">
                                <p class="font-medium">
                                    {{ $variant->displayLabel() }}
                                    @if ($variantStatus !== 'active')
                                        <x-admin.status-pill :status="$variantStatus" class="ml-1" />
                                    @endif
                                </p>
                                @if ($variant->title && $variant->formattedAttributes())
                                    <p class="text-xs text-muted">{{ $variant->formattedAttributes() }}</p>
                                @endif
                            </td>
                            <td class="p-3 text-right"><x-admin.money :amount="$variant->currentPrice()" /></td>
                            <td class="p-3 text-right">
                                <span class="tabular-nums">{{ number_format($variantStock) }}</span>
                                @if ($variantStock <= 0)
                                    <x-admin.status-pill status="out_of_stock" label="Out" class="ml-1" />
                                @elseif ($variantLow)
                                    <x-admin.status-pill status="low_stock" label="Low" class="ml-1" />
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($variant->is_default)
                                    <span class="badge badge-champagne badge-sm">Default</span>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                    <a href="{{ route('admin.inventory.movements', ['variant' => $variant->id]) }}"
                                       class="text-xs text-muted hover:text-charcoal hover:underline">Ledger</a>

                                    @can('products.update')
                                        @unless ($variant->is_default)
                                            <form action="{{ route('admin.products.variants.default', [$product, $variant]) }}"
                                                  method="post" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        class="text-xs text-muted hover:text-charcoal hover:underline">
                                                    Set default
                                                </button>
                                            </form>
                                        @endunless

                                        <button type="button" class="text-xs font-medium text-champagne-dark hover:underline"
                                                x-on:click="$dispatch('open-modal-edit-variant-{{ $variant->id }}')">
                                            Edit
                                        </button>
                                    @endcan

                                    @can('products.delete')
                                        <x-admin.confirm :action="route('admin.products.variants.destroy', [$product, $variant])"
                                                         title="Remove this variant?"
                                                         :message="'Variant “'.($variant->title ?: $variant->sku).'” and its stock history link will be removed.'"
                                                         label="Remove"
                                                         confirm-label="Remove variant" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-admin.table>
        @endif
    </x-admin.card>

    {{-- Related products --------------------------------------------------- --}}
    <x-admin.card id="product-recommendations" title="Recommendations" subtitle="Choose related products, upsells and cross-sells for the storefront." class="mt-6 scroll-mt-32">
        <div class="space-y-6">
            @foreach ([
                ['label' => 'Related', 'type' => 'related', 'items' => $relatedProducts],
                ['label' => 'Upsells', 'type' => 'upsell', 'items' => $upsellProducts],
                ['label' => 'Cross-sells', 'type' => 'cross_sell', 'items' => $crossSellProducts],
            ] as $group)
                <div>
                    <h3 class="text-xs uppercase tracking-widest text-muted mb-2">{{ $group['label'] }}</h3>

                    @if ($group['items']->isEmpty())
                        <p class="text-sm text-muted mb-2">None linked yet.</p>
                    @else
                        <ul class="divide-y divide-charcoal/5 border border-charcoal/10 rounded-lg overflow-hidden mb-2">
                            @foreach ($group['items'] as $item)
                                <li class="flex items-center justify-between gap-3 px-3 py-2 bg-porcelain">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.edit', $item) }}"
                                           class="text-sm font-medium hover:text-champagne-dark">{{ $item->name }}</a>
                                        <span class="text-xs text-muted font-mono ml-1">{{ $item->sku }}</span>
                                    </div>

                                    @can('products.update')
                                        <x-admin.confirm :action="route('admin.products.relations.destroy', [$product, $item->id]).'?relation_type='.$group['type']"
                                                         method="DELETE"
                                                         title="Remove this link?"
                                                         :message="'“'.$item->name.'” will no longer appear in this group.'"
                                                         label="Remove"
                                                         confirm-label="Remove link" />
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            @can('products.update')
                <form action="{{ route('admin.products.relations.store', $product) }}" method="post"
                      class="flex flex-wrap items-end gap-3 border-t border-charcoal/5 pt-4">
                    @csrf

                    <div class="flex-1 min-w-[220px]">
                        <label for="related-product" class="text-xs text-muted">Product</label>
                        <select id="related-product" name="related_product_id" required>
                            <option value="">Choose a product…</option>
                            @foreach ($productOptions as $option)
                                <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->sku ?? 'no SKU' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="relation-type" class="text-xs text-muted">Relationship</label>
                        <select id="relation-type" name="relation_type">
                            <option value="related">Related</option>
                            <option value="upsell">Upsell</option>
                            <option value="cross_sell">Cross-sell</option>
                        </select>
                    </div>

                    <x-admin.button type="submit" variant="outline" icon="plus">Add link</x-admin.button>
                </form>
            @endcan
        </div>
    </x-admin.card>

    {{-- Activity ------------------------------------------------------------ --}}
    <x-admin.card id="product-activity" title="Recent activity" subtitle="Changes made to this product and its variants." class="mt-6 scroll-mt-32">
        @if ($activity->isEmpty())
            <p class="text-sm text-muted">No activity recorded yet.</p>
        @else
            <ol class="relative border-l border-charcoal/10 ml-2 space-y-4">
                @foreach ($activity as $entry)
                    <li class="ml-4">
                        <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-champagne"></span>
                        <p class="text-sm font-medium">
                            {{ \Illuminate\Support\Str::headline(str_replace(['.', '_'], ' ', $entry->action)) }}
                        </p>
                        <p class="text-xs text-muted">
                            {{ $entry->actor_email ?? 'System' }}
                            · {{ $entry->created_at?->format('M j, Y H:i') }}
                            · {{ $entry->created_at?->diffForHumans() }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @endif
    </x-admin.card>

    {{-- Modals -------------------------------------------------------------- --}}
    @can('products.update')
        <x-admin.modal name="upload-images" title="Upload images">
            <form action="{{ route('admin.products.images.store', $product) }}" method="post"
                  enctype="multipart/form-data" class="space-y-4" id="product-image-form">
                @csrf

                <x-admin.field name="images" label="Images" :required="true" id="upload-images-files"
                               hint="JPG, PNG, WebP or GIF. Up to 10 images, 5 MB each.">
                    <x-admin.input name="images[]" id="upload-images-files" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required />
                </x-admin.field>

                <div id="product-image-error" class="hidden text-sm text-danger" role="alert" tabindex="-1"></div>
                <div id="product-image-preview" class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-live="polite"></div>

                <x-admin.field name="alt_text" label="Alt text" id="upload-images-alt"
                               hint="Applied to every image in this upload.">
                    <x-admin.input name="alt_text" id="upload-images-alt" />
                </x-admin.field>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline btn-sm"
                            x-on:click="$dispatch('close-modal-upload-images')">Cancel</button>
                    <button type="submit" class="btn-gold btn-sm">Upload</button>
                </div>
            </form>
        </x-admin.modal>
    @endcan

    @can('products.create')
        <x-admin.modal name="add-variant" title="Add a variant">
            <form action="{{ route('admin.products.variants.store', $product) }}" method="post" class="space-y-4">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <x-admin.field name="title" label="Variant title" id="add-variant-title">
                        <x-admin.input name="title" id="add-variant-title" placeholder="e.g. Gold / 16" />
                    </x-admin.field>
                    <x-admin.field name="sku" label="SKU" id="add-variant-sku">
                        <x-admin.input name="sku" id="add-variant-sku" />
                    </x-admin.field>
                    <x-admin.field name="regular_price" label="Regular price (Rs.)" :required="true" id="add-variant-regular">
                        <x-admin.input name="regular_price" id="add-variant-regular" type="number" step="0.01"
                                       :value="$product->regular_price" required />
                    </x-admin.field>
                    <x-admin.field name="sale_price" label="Sale price (Rs.)" id="add-variant-sale">
                        <x-admin.input name="sale_price" id="add-variant-sale" type="number" step="0.01" />
                    </x-admin.field>
                    <x-admin.field name="stock_quantity" label="Opening stock" :required="true" id="add-variant-stock">
                        <x-admin.input name="stock_quantity" id="add-variant-stock" type="number" min="0" value="0" required />
                    </x-admin.field>
                    <x-admin.field name="low_stock_threshold" label="Low stock threshold" id="add-variant-threshold">
                        <x-admin.input name="low_stock_threshold" id="add-variant-threshold" type="number" min="0" value="5" />
                    </x-admin.field>
                    <x-admin.field name="attributes[Size]" label="Size" id="add-variant-size">
                        <x-admin.input name="attributes[Size]" id="add-variant-size" />
                    </x-admin.field>
                    <x-admin.field name="attributes[Colour]" label="Colour" id="add-variant-colour">
                        <x-admin.input name="attributes[Colour]" id="add-variant-colour" />
                    </x-admin.field>
                    <x-admin.field name="barcode" label="Barcode" id="add-variant-barcode">
                        <x-admin.input name="barcode" id="add-variant-barcode" />
                    </x-admin.field>
                    <x-admin.field name="weight_grams" label="Weight (grams)" id="add-variant-weight">
                        <x-admin.input name="weight_grams" id="add-variant-weight" type="number" step="0.01" min="0" />
                    </x-admin.field>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <x-admin.field name="status" label="Status" id="add-variant-status">
                        <x-admin.select name="status" id="add-variant-status" selected="active"
                                        :options="['active' => 'Active', 'inactive' => 'Inactive', 'discontinued' => 'Discontinued']" />
                    </x-admin.field>
                </div>

                <div class="space-y-2.5">
                    <x-admin.checkbox name="is_default" label="Use as the default variant" />
                    <x-admin.checkbox name="allow_backorder" label="Allow backorders for this variant" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline btn-sm"
                            x-on:click="$dispatch('close-modal-add-variant')">Cancel</button>
                    <button type="submit" class="btn-gold btn-sm">Add variant</button>
                </div>
            </form>
        </x-admin.modal>

        <x-admin.modal name="generate-variants" title="Generate variant matrix" max-width="max-w-2xl">
            <form action="{{ route('admin.products.variants.generate', $product) }}" method="post" class="space-y-4">
                @csrf

                <p class="text-sm text-muted">
                    Enter comma-separated values. Every combination is created once and existing combinations are skipped.
                </p>

                <div class="space-y-3">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="grid sm:grid-cols-[160px_1fr] gap-3">
                            <x-admin.field :name="'attributes['.$i.'][name]'" :label="$i === 0 ? 'Attribute name' : 'Another attribute'"
                                           :id="'matrix-name-'.$i">
                                <x-admin.input :name="'attributes['.$i.'][name]'" :id="'matrix-name-'.$i"
                                               :value="['Size','Colour','Metal'][$i] ?? ''" />
                            </x-admin.field>
                            <x-admin.field :name="'attributes['.$i.'][values]'" label="Values (comma separated)"
                                           :id="'matrix-values-'.$i">
                                <x-admin.input :name="'attributes['.$i.'][values]'" :id="'matrix-values-'.$i"
                                               :value="['14,16,18','Gold,Silver,Rose Gold',''][$i] ?? ''" />
                            </x-admin.field>
                        </div>
                    @endfor
                </div>

                <div class="grid sm:grid-cols-2 gap-4 border-t border-charcoal/5 pt-4">
                    <x-admin.field name="regular_price" label="Regular price (Rs.)" :required="true" id="matrix-regular">
                        <x-admin.input name="regular_price" id="matrix-regular" type="number" step="0.01"
                                       :value="$product->regular_price" required />
                    </x-admin.field>
                    <x-admin.field name="sale_price" label="Sale price (Rs.)" id="matrix-sale">
                        <x-admin.input name="sale_price" id="matrix-sale" type="number" step="0.01" />
                    </x-admin.field>
                    <x-admin.field name="stock_quantity" label="Opening stock per variant" :required="true" id="matrix-stock">
                        <x-admin.input name="stock_quantity" id="matrix-stock" type="number" min="0" value="0" required />
                    </x-admin.field>
                    <x-admin.field name="low_stock_threshold" label="Low stock threshold" id="matrix-threshold">
                        <x-admin.input name="low_stock_threshold" id="matrix-threshold" type="number" min="0" value="5" />
                    </x-admin.field>
                    <x-admin.field name="sku_prefix" label="SKU prefix" id="matrix-prefix" hint="Defaults to the product SKU.">
                        <x-admin.input name="sku_prefix" id="matrix-prefix" :value="$product->sku" />
                    </x-admin.field>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn-outline btn-sm"
                            x-on:click="$dispatch('close-modal-generate-variants')">Cancel</button>
                    <button type="submit" class="btn-gold btn-sm">Generate variants</button>
                </div>
            </form>
        </x-admin.modal>
    @endcan

    @can('products.update')
        @foreach ($product->variants as $variant)
            <x-admin.modal :name="'edit-variant-'.$variant->id" title="Edit variant">
                <form action="{{ route('admin.products.variants.update', [$product, $variant]) }}" method="post" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-admin.field name="title" label="Variant title" :id="'variant-'.$variant->id.'-title'">
                            <x-admin.input name="title" :id="'variant-'.$variant->id.'-title'" :value="$variant->title" />
                        </x-admin.field>
                        <x-admin.field name="sku" label="SKU" :id="'variant-'.$variant->id.'-sku'">
                            <x-admin.input name="sku" :id="'variant-'.$variant->id.'-sku'" :value="$variant->sku" />
                        </x-admin.field>
                        <x-admin.field name="regular_price" label="Regular price (Rs.)" :required="true" :id="'variant-'.$variant->id.'-regular'">
                            <x-admin.input name="regular_price" :id="'variant-'.$variant->id.'-regular'" type="number"
                                           step="0.01" :value="$variant->regular_price" required />
                        </x-admin.field>
                        <x-admin.field name="sale_price" label="Sale price (Rs.)" :id="'variant-'.$variant->id.'-sale'">
                            <x-admin.input name="sale_price" :id="'variant-'.$variant->id.'-sale'" type="number"
                                           step="0.01" :value="$variant->sale_price" />
                        </x-admin.field>
                        <x-admin.field name="barcode" label="Barcode" :id="'variant-'.$variant->id.'-barcode'">
                            <x-admin.input name="barcode" :id="'variant-'.$variant->id.'-barcode'" :value="$variant->barcode" />
                        </x-admin.field>
                        <x-admin.field name="weight_grams" label="Weight (grams)" :id="'variant-'.$variant->id.'-weight'">
                            <x-admin.input name="weight_grams" :id="'variant-'.$variant->id.'-weight'" type="number"
                                           step="0.01" min="0" :value="$variant->weight_grams" />
                        </x-admin.field>
                        <x-admin.field name="low_stock_threshold" label="Low stock threshold" :id="'variant-'.$variant->id.'-threshold'">
                            <x-admin.input name="low_stock_threshold" :id="'variant-'.$variant->id.'-threshold'" type="number"
                                           min="0" :value="$variant->low_stock_threshold" />
                        </x-admin.field>
                        <x-admin.field name="status" label="Status" :id="'variant-'.$variant->id.'-status'">
                            <x-admin.select name="status" :id="'variant-'.$variant->id.'-status'"
                                            :selected="$variant->status ?? 'active'"
                                            :options="['active' => 'Active', 'inactive' => 'Inactive', 'discontinued' => 'Discontinued']" />
                        </x-admin.field>
                    </div>

                    @if ($variant->attributes)
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ($variant->attributes as $attributeName => $attributeValue)
                                <x-admin.field :name="'attributes['.$attributeName.']'" :label="$attributeName"
                                               :id="'variant-'.$variant->id.'-attr-'.\Illuminate\Support\Str::slug($attributeName)">
                                    <x-admin.input :name="'attributes['.$attributeName.']'"
                                                   :id="'variant-'.$variant->id.'-attr-'.\Illuminate\Support\Str::slug($attributeName)"
                                                   :value="$attributeValue" />
                                </x-admin.field>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-2.5">
                        <x-admin.checkbox name="is_default" label="Use as the default variant" :checked="$variant->is_default" />
                        <x-admin.checkbox name="allow_backorder" label="Allow backorders for this variant" :checked="$variant->allow_backorder" />
                    </div>

                    <p class="text-xs text-muted">
                        Current stock: <strong class="text-charcoal">{{ number_format($variant->availableStock()) }}</strong> available
                        ({{ number_format($variant->stock_quantity) }} on hand). Adjust it through the
                        <a href="{{ route('admin.inventory.movements', ['variant' => $variant->id]) }}"
                           class="text-champagne-dark hover:underline">inventory ledger</a>,
                        so every change is auditable.
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="btn-outline btn-sm"
                                x-on:click="$dispatch('close-modal-edit-variant-{{ $variant->id }}')">Cancel</button>
                        <button type="submit" class="btn-gold btn-sm">Save variant</button>
                    </div>
                </form>
            </x-admin.modal>
        @endforeach
    @endcan

    <div class="fixed inset-x-0 bottom-0 z-[var(--z-sticky)] border-t border-charcoal/10 bg-porcelain/95 px-4 py-3 shadow-lg backdrop-blur md:px-6">
        <div class="container-haars flex items-center justify-between gap-3">
            <span class="hidden text-xs text-muted sm:block">Remember to save your changes.</span>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('admin.products.index') }}" class="btn-outline btn-sm">Back to products</a>
                <button type="submit" form="product-edit-form" class="btn-gold btn-sm">Save changes</button>
            </div>
        </div>
    </div>
    </div>
=======
            <div class="mt-6 space-y-4">
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
                                            const results = await ImageCompressor.compressAll(Array.from(this.files), { maxSizeMB: 4 });
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
                                        alert('One or more files are still too large (server limit: {{ ini_get('upload_max_filesize') }}). Please choose smaller files or use URL-based upload.');
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

                {{-- Videos & Reels --}}
                <div class="card p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-serif text-lg">Videos &amp; Reels</h2>
                    </div>

                    @if ($product->videos->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                            @foreach ($product->videos as $video)
                                <div class="border border-charcoal/10 rounded-lg overflow-hidden bg-charcoal">
                                    <video src="{{ $video->url() }}" @if ($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif controls playsinline preload="metadata" class="w-full aspect-[9/16] object-cover bg-black"></video>
                                    <div class="p-3 bg-porcelain text-xs flex items-start justify-between gap-2">
                                        <div>
                                            <p class="font-medium">{{ $video->title ?: ucfirst($video->type) }}</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span class="badge bg-charcoal/10 text-charcoal">{{ ucfirst($video->type) }}</span>
                                                @if ($video->is_primary)
                                                    <span class="badge bg-champagne/30 text-charcoal">Primary</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex gap-2 shrink-0">
                                            @unless ($video->is_primary)
                                                <form action="{{ route('admin.products.videos.primary', [$product, $video]) }}" method="post" class="inline">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="hover:text-champagne-dark">Primary</button>
                                                </form>
                                            @endunless
                                            <form action="{{ route('admin.products.videos.destroy', [$product, $video]) }}" method="post" class="inline" onsubmit="return confirm('Remove this video?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-danger hover:underline">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-muted mb-4">No videos or reels uploaded yet.</p>
                    @endif

                    <form action="{{ route('admin.products.videos.store', $product) }}" method="post" enctype="multipart/form-data" class="space-y-3 border-t border-charcoal/5 pt-4">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="videos">Upload videos / reels</label>
                                <input type="file" id="videos" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime,video/x-m4v" required>
                                @error('videos') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                @error('videos.*') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                <p class="text-xs text-muted mt-1">MP4, WebM or MOV. Max 100 MB each. Reels look best in vertical 9:16.</p>
                            </div>
                            <div>
                                <label for="video_type">Type</label>
                                <select id="video_type" name="type">
                                    <option value="video" @selected(old('type', 'video') === 'video')>Video</option>
                                    <option value="reel" @selected(old('type') === 'reel')>Reel</option>
                                </select>
                            </div>
                            <div>
                                <label for="video_title">Title (optional)</label>
                                <input type="text" id="video_title" name="title" value="{{ old('title') }}" maxlength="150">
                            </div>
                            <div>
                                <label for="thumbnails">Poster images (optional, matched by order)</label>
                                <input type="file" id="thumbnails" name="thumbnails[]" multiple accept="image/*">
                                @error('thumbnails.*') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn-gold text-sm">Upload videos</button>
                    </form>
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
>>>>>>> origin/main
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editForm = document.getElementById('product-edit-form');

            if (editForm) {
                let dirty = false;

                editForm.addEventListener('input', function () { dirty = true; });
                editForm.addEventListener('change', function () { dirty = true; });
                editForm.addEventListener('submit', function () { dirty = false; });

                window.addEventListener('beforeunload', function (event) {
                    if (!dirty) return;
                    event.preventDefault();
                    event.returnValue = '';
                });
            }

            const form = document.getElementById('product-image-form');
            if (!form) return;

            const fileInput = form.querySelector('input[name="images[]"]');
            const preview = document.getElementById('product-image-preview');
            const error = document.getElementById('product-image-error');
            if (!fileInput || !preview || !error) return;

            function renderPreviews() {
                preview.replaceChildren();
                error.textContent = '';
                error.classList.add('hidden');
                const files = Array.from(fileInput.files || []);
                if (files.length > 10 || files.some((file) => file.size > 5 * 1024 * 1024)) {
                    error.textContent = files.length > 10
                        ? 'Choose no more than 10 images.'
                        : 'Each image must be 5 MB or smaller.';
                    error.classList.remove('hidden');
                }

                files.forEach((file) => {
                    const tile = document.createElement('div');
                    tile.className = 'overflow-hidden rounded-lg border border-charcoal/10 bg-white';
                    const image = document.createElement('img');
                    image.src = URL.createObjectURL(file);
                    image.alt = 'Preview of ' + file.name;
                    image.className = 'aspect-square w-full object-cover';
                    image.onload = () => URL.revokeObjectURL(image.src);
                    const caption = document.createElement('p');
                    caption.className = 'truncate px-2 py-1.5 text-xs text-muted';
                    caption.textContent = file.name;
                    tile.append(image, caption);
                    preview.append(tile);
                });
            }

            fileInput.addEventListener('change', renderPreviews);
            form.addEventListener('submit', function (event) {
                if (fileInput.files.length > 10 || Array.from(fileInput.files).some((file) => file.size > 5 * 1024 * 1024)) {
                    event.preventDefault();
                    renderPreviews();
                    error.focus();
                }
            });
        });
    </script>
@endpush
