@extends('admin.layout')

@section('title', 'Edit Collection — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    @php
        $rules = old('rules', $collection->rules ?? []);
        $type = old('type', $collection->type);
        $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'];
        $categoryOptions = $categories->pluck('name', 'id')->all();
    @endphp

    <x-admin.header :title="'Edit: '.$collection->name"
                    subtitle="Curate products, define automatic rules, schedule the edit and tune search metadata.">
        <x-slot:actions>
            <x-admin.button :href="route('collections.show', $collection->slug)" target="_blank" rel="noopener"
                            variant="outline" size="sm" icon="external-link">
                Preview
            </x-admin.button>
            <x-admin.button :href="route('admin.collections.index')" variant="ghost" size="sm" icon="arrow-left">
                Back to collections
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.error-summary />

    <form id="collection-edit-form" action="{{ route('admin.collections.update', $collection) }}" method="post">
        @csrf
        @method('PUT')

        <div class="grid lg:grid-cols-[1fr_330px] gap-6 items-start" x-data="{ type: @js($type) }">
            <div class="space-y-5">
                <x-admin.card title="Details">
                    <div class="space-y-4">
                        <x-admin.field name="name" label="Collection name" :required="true">
                            <x-admin.input name="name" :value="$collection->name" required />
                        </x-admin.field>

                        <x-admin.field name="description" label="Description">
                            <x-admin.textarea name="description" :value="$collection->description" :rows="5" />
                        </x-admin.field>

                        <x-admin.field name="banner_image" label="Banner image URL"
                                       hint="Use a media path such as /storage/collections/bridal.jpg.">
                            <x-admin.input name="banner_image" :value="$collection->banner_image" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <x-admin.card title="Automatic rules"
                               subtitle="Rule-based collections rebuild themselves from matching active products.">
                    <div class="space-y-4">
                        <x-admin.field name="type" label="Collection type" :required="true">
                            <x-admin.select name="type" :selected="$type" :options="['manual' => 'Manual selection', 'rule_based' => 'Rule based']"
                                            x-model="type" />
                        </x-admin.field>

                        <div x-show="type === 'rule_based'" x-cloak class="space-y-4">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <x-admin.field name="rules[min_price]" label="Minimum price (Rs.)">
                                    <x-admin.input name="rules[min_price]" type="number" step="0.01" :value="$rules['min_price'] ?? null" />
                                </x-admin.field>

                                <x-admin.field name="rules[max_price]" label="Maximum price (Rs.)">
                                    <x-admin.input name="rules[max_price]" type="number" step="0.01" :value="$rules['max_price'] ?? null" />
                                </x-admin.field>
                            </div>

                            <x-admin.field name="rules[category_id]" label="Category">
                                <x-admin.select name="rules[category_id]" :selected="$rules['category_id'] ?? null"
                                                :options="$categoryOptions" placeholder="Any category" />
                            </x-admin.field>

                            <x-admin.checkbox name="rules[is_featured]" label="Featured products only"
                                              :checked="! empty($rules['is_featured'])" />
                        </div>

                        <div x-show="type === 'manual'" x-cloak class="text-sm text-muted">
                            Products are attached manually in the picker below.
                        </div>
                    </div>

                    <x-slot:footer>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-muted">Matching products right now</p>
                            <span class="badge badge-champagne">{{ number_format($ruleMatchCount) }}</span>
                        </div>
                    </x-slot:footer>
                </x-admin.card>
            </div>

            <aside class="space-y-5">
                <x-admin.card title="Publishing">
                    <div class="space-y-4">
                        <x-admin.field name="status" label="Status" :required="true">
                            <x-admin.select name="status" :selected="$collection->status" :options="$statusOptions" />
                        </x-admin.field>

                        <x-admin.field name="match_condition" label="Rule matching"
                                       hint="Only used by rule-based collections.">
                            <x-admin.select name="match_condition" :selected="$collection->match_condition"
                                            :options="['all' => 'Match all rules', 'any' => 'Match any rule']" />
                        </x-admin.field>

                        <x-admin.field name="sort_order" label="Sort order" hint="Lower numbers appear first.">
                            <x-admin.input name="sort_order" type="number" min="0" :value="$collection->sort_order" />
                        </x-admin.field>

                        <x-admin.checkbox name="show_on_homepage" label="Show on the homepage"
                                          :checked="$collection->show_on_homepage" />
                    </div>
                </x-admin.card>

                <x-admin.card title="Schedule">
                    <div class="space-y-4">
                        <x-admin.field name="starts_at" label="Starts at">
                            <x-admin.input name="starts_at" type="datetime-local"
                                           :value="old('starts_at', optional($collection->starts_at)->format('Y-m-d\TH:i'))" />
                        </x-admin.field>

                        <x-admin.field name="ends_at" label="Ends at">
                            <x-admin.input name="ends_at" type="datetime-local"
                                           :value="old('ends_at', optional($collection->ends_at)->format('Y-m-d\TH:i'))" />
                        </x-admin.field>

                        <p class="text-xs text-muted">Leave both blank for an always-on edit.</p>
                    </div>
                </x-admin.card>

                <x-admin.card title="SEO">
                    <div class="space-y-4">
                        <x-admin.field name="seo_title" label="SEO title">
                            <x-admin.input name="seo_title" :value="$collection->seo_title" />
                        </x-admin.field>

                        <x-admin.field name="seo_description" label="Meta description">
                            <x-admin.textarea name="seo_description" :value="$collection->seo_description" :rows="3" />
                        </x-admin.field>

                        <x-admin.field name="seo_image" label="Social share image URL">
                            <x-admin.input name="seo_image" :value="$collection->seo_image" />
                        </x-admin.field>
                    </div>
                </x-admin.card>

                <div class="flex gap-2">
                    <x-admin.button type="submit" class="flex-1" icon="save">Save changes</x-admin.button>
                    <x-admin.button :href="route('admin.collections.index')" variant="ghost">Cancel</x-admin.button>
                </div>
            </aside>
        </div>
    </form>

    @if ($collection->type === 'manual')
        <x-admin.card title="Products in this collection"
                      subtitle="Attach products and use the arrows to control storefront order." :padding="false" class="mt-6">
            <div class="p-5 space-y-4">
                @if ($collection->products->isEmpty())
                    <x-admin.empty icon="package" title="No products attached"
                                   description="Pick a product below to start curating this edit." />
                @else
                    <ul class="divide-y divide-charcoal/5 border border-charcoal/10 rounded-lg overflow-hidden">
                        @foreach ($collection->products as $product)
                            <li class="flex flex-wrap items-center justify-between gap-3 px-3 py-2.5 bg-porcelain">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-6 text-center text-xs text-muted tabular-nums">{{ $loop->iteration }}</span>

                                    <div class="w-9 h-9 rounded bg-ivory-alt overflow-hidden flex-shrink-0">
                                        @if ($path = $product->primaryImagePath())
                                            <img src="{{ \App\Support\Media::url($path) }}" alt="" class="w-full h-full object-cover">
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <a href="{{ route('admin.products.edit', $product) }}"
                                           class="text-sm font-medium hover:text-champagne-dark">{{ $product->name }}</a>
                                        <div class="text-xs text-muted font-mono">{{ $product->sku ?? '—' }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1">
                                    <form action="{{ route('admin.collections.products.move', [$collection, $product, 'up']) }}" method="post">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $product->name }} up" title="Move up">
                                            <x-admin.icon name="chevron-up" :size="14" />
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.collections.products.move', [$collection, $product, 'down']) }}" method="post">
                                        @csrf
                                        <button type="submit" class="p-1.5 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move {{ $product->name }} down" title="Move down">
                                            <x-admin.icon name="chevron-down" :size="14" />
                                        </button>
                                    </form>

                                    <x-admin.confirm :action="route('admin.collections.products.destroy', [$collection, $product])"
                                                     title="Remove from collection?"
                                                     :message="'“'.$product->name.'” stays in the catalogue; only this collection link is removed.'"
                                                     label="Remove"
                                                     confirm-label="Remove product" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form action="{{ route('admin.collections.products.store', $collection) }}" method="post"
                      class="flex flex-wrap items-end gap-3 border-t border-charcoal/5 pt-4">
                    @csrf

                    <div class="flex-1 min-w-[240px]">
                        <label for="collection-product" class="text-xs text-muted">Add a product</label>
                        <select id="collection-product" name="product_id" required>
                            <option value="">Choose a product…</option>
                            @foreach ($productOptions as $option)
                                @unless ($collection->products->contains('id', $option->id))
                                    <option value="{{ $option->id }}">{{ $option->name }} ({{ $option->sku ?? 'no SKU' }})</option>
                                @endunless
                            @endforeach
                        </select>
                    </div>

                    <x-admin.button type="submit" variant="outline" icon="plus">Add to collection</x-admin.button>
                </form>
            </div>
        </x-admin.card>
    @else
        <x-admin.card title="Automatic collection" class="mt-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-charcoal-soft">
                    This collection is generated from its rules and currently matches
                    <span class="font-medium">{{ number_format($ruleMatchCount) }}</span> active product(s).
                    Adjust the rules above to change the selection.
                </p>

                <x-admin.button :href="route('admin.products.index')" variant="outline" size="sm" icon="package">
                    Browse products
                </x-admin.button>
            </div>
        </x-admin.card>
    @endif

    @can('collections.delete')
        <div class="mt-6 flex justify-end">
            <x-admin.confirm :action="route('admin.collections.destroy', $collection)"
                             title="Archive this collection?"
                             :message="'“'.$collection->name.'” will be hidden from the storefront. Products are not deleted.'"
                             label="Archive collection"
                             confirm-label="Archive collection" />
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('collection-edit-form');
            if (!form) return;

            let dirty = false;

            form.addEventListener('input', function () { dirty = true; });
            form.addEventListener('change', function () { dirty = true; });
            form.addEventListener('submit', function () { dirty = false; });

            window.addEventListener('beforeunload', function (event) {
                if (!dirty) return;
                event.preventDefault();
                event.returnValue = '';
            });
        });
    </script>
@endpush
=======
    @php $rules = old('rules', $collection->rules ?? []); @endphp
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl md:text-3xl">Edit Collection</h1>
        <a href="{{ route('admin.collections.index') }}" class="btn-ghost text-sm">Back</a>
    </div>

    <form action="{{ route('admin.collections.update', $collection) }}" method="post">
        @csrf
        @method('PUT')
        <div class="grid lg:grid-cols-[1fr_320px] gap-6">
            <div class="space-y-4">
                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Details</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required value="{{ old('name', $collection->name) }}">
                            @error('name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5" class="ckeditor">{{ old('description', $collection->description) }}</textarea>
                        </div>
                        <div>
                            <label for="banner_image">Banner image URL</label>
                            <input type="text" id="banner_image" name="banner_image" value="{{ old('banner_image', $collection->banner_image) }}">
                        </div>
                    </div>
                </div>

                <div class="card p-5">
                    <h2 class="font-serif text-lg mb-4">Rules</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="rule_min_price">Minimum price (Rs.)</label>
                            <input type="number" step="0.01" id="rule_min_price" name="rules[min_price]" value="{{ $rules['min_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="rule_max_price">Maximum price (Rs.)</label>
                            <input type="number" step="0.01" id="rule_max_price" name="rules[max_price]" value="{{ $rules['max_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="rule_category">Category ID</label>
                            <input type="number" id="rule_category" name="rules[category_id]" value="{{ $rules['category_id'] ?? '' }}">
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="rules[is_featured]" value="1" @checked(!empty($rules['is_featured']))>
                                Featured only
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="card p-5 space-y-4">
                    <div>
                        <label for="type">Type</label>
                        <select id="type" name="type">
                            <option value="manual" @selected(old('type', $collection->type) === 'manual')>Manual</option>
                            <option value="rule_based" @selected(old('type', $collection->type) === 'rule_based')>Rule based</option>
                        </select>
                    </div>
                    <div>
                        <label for="match_condition">Match condition</label>
                        <select id="match_condition" name="match_condition">
                            <option value="all" @selected(old('match_condition', $collection->match_condition) === 'all')>Match all rules</option>
                            <option value="any" @selected(old('match_condition', $collection->match_condition) === 'any')>Match any rule</option>
                        </select>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" @selected(old('status', $collection->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $collection->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="sort_order">Sort order</label>
                        <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $collection->sort_order) }}">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="show_on_homepage" value="1" @checked(old('show_on_homepage', $collection->show_on_homepage))>
                        Show on homepage
                    </label>
                </div>

                <div class="card p-5 space-y-4">
                    <h3 class="font-serif text-base">Schedule</h3>
                    <div>
                        <label for="starts_at">Starts at</label>
                        <input type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', optional($collection->starts_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div>
                        <label for="ends_at">Ends at</label>
                        <input type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', optional($collection->ends_at)->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-full">Save Changes</button>
                @can('collections.delete')
                <form action="{{ route('admin.collections.destroy', $collection) }}" method="post" onsubmit="return confirm('Archive this collection?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-ghost w-full text-danger">Archive Collection</button>
                </form>
                @endcan
            </aside>
        </div>
    </form>
@endsection
>>>>>>> origin/main
