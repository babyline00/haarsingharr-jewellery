@php
    $isEdit = isset($menu);
    $formAction = $isEdit ? route('admin.menus.update', $menu) : route('admin.menus.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

<x-admin.card>
    <form action="{{ $formAction }}" method="post"
          x-data='{
            items: {{ old("items", json_encode($menu->items ?? [])) }},
            pages: @json($linkOptions["pages"]),
            categories: @json($linkOptions["categories"]),
            collections: @json($linkOptions["collections"]),

            newItem() {
                return { label: "", type: "custom", link: "", is_visible: true, children: [] };
            },
            addChild(item) {
                item.children.push({ label: "", type: "custom", link: "", is_visible: true });
            },
            move(list, index, direction) {
                const target = direction === "up" ? index - 1 : index + 1;
                if (target < 0 || target >= list.length) return;
                [list[index], list[target]] = [list[target], list[index]];
            },
            linkOptions(type) {
                return type === "page" ? this.pages
                    : type === "category" ? this.categories
                    : type === "collection" ? this.collections : null;
            }
          }'>
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="grid md:grid-cols-3 gap-4 mb-6">
            <x-admin.field name="name" label="Menu name *">
                <x-admin.input id="name" name="name" required :value="old('name', $menu->name ?? '')" />
            </x-admin.field>

            <x-admin.field name="location" label="Storefront location *"
                           hint="Only the first menu per location is used by the storefront.">
                <x-admin.select id="location" name="location" :selected="old('location', $menu->location ?? 'main')"
                                :options="collect($locations)->mapWithKeys(fn ($l) => [$l => ucfirst($l)])->all()" />
            </x-admin.field>
        </div>

        <input type="hidden" name="items" :value="JSON.stringify(items.filter(it => it.label))">

        <h2 class="font-serif text-lg mb-2">Menu items</h2>
        <p class="text-xs text-muted mb-3">One nesting level: top-level rows can carry a dropdown of children.</p>

        <div class="space-y-3" x-cloak>
            <template x-for="(item, i) in items" :key="'top-'+i">
                <div class="card p-4"
                     x-show="item.label !== undefined">
                    <div class="grid md:grid-cols-[1fr_150px_auto] gap-3 items-end">
                        <x-admin.field name="item-label" label="Label">
                            <input type="text" x-model="items[i].label"
                                   placeholder="e.g. Bridal" aria-label="Item label">
                        </x-admin.field>

                        <x-admin.field name="item-type" label="Links to">
                            <select x-model="items[i].type" aria-label="Link type"
                                    class="text-sm">
                                <option value="custom">Custom URL</option>
                                <option value="page">CMS page</option>
                                <option value="category">Category</option>
                                <option value="collection">Collection</option>
                            </select>
                        </x-admin.field>

                        <div class="flex items-center gap-2 pb-1">
                            <button type="button" x-on:click="move(items, i, 'up')"
                                    class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                    aria-label="Move item up"><x-admin.icon name="chevron-up" :size="14" /></button>
                            <button type="button" x-on:click="move(items, i, 'down')"
                                    class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                    aria-label="Move item down"><x-admin.icon name="chevron-down" :size="14" /></button>
                            <button type="button" x-on:click="items.splice(i, 1)"
                                    class="text-xs text-danger hover:underline" aria-label="Remove item">Remove</button>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-[1fr_auto] gap-3 items-end mt-1">
                        <template x-if="linkOptions(items[i].type)">
                            <select x-model="items[i].link" aria-label="Link target" class="text-sm">
                                <template x-for="option in linkOptions(items[i].type)">
                                    <option x-bind:value="items[i].type === 'page' ? option.slug : option.id"
                                            x-text="items[i].type === 'page' ? option.title : option.name"
                                            x-bind:selected="String(option[items[i].type === 'page' ? 'slug' : 'id']) === String(items[i].link)">
                                    </option>
                                </template>
                            </select>
                        </template>

                        <template x-if="! linkOptions(items[i].type)">
                            <input type="text" x-model="items[i].link" placeholder="https://… or /shop"
                                   aria-label="Custom URL" class="text-sm">
                        </template>

                        <label class="flex items-center gap-2 text-sm whitespace-nowrap pb-1">
                            <input type="checkbox" x-model="items[i].is_visible">
                            Visible
                        </label>
                    </div>

                    @can('menus.update')
                        <div class="mt-4 border-t border-charcoal/5 pt-3">
                            <p class="text-xs font-medium text-muted mb-2 uppercase tracking-widest">Dropdown children</p>

                            <template x-for="(child, ci) in (items[i].children || [])" :key="'child-'+i+'-'+ci">
                                <div class="grid md:grid-cols-[1fr_150px_1fr_auto] gap-2 items-end mb-2 pl-4">
                                    <input type="text" x-model="items[i].children[ci].label"
                                           placeholder="Label" aria-label="Child label" class="text-sm">
                                    <select x-model="items[i].children[ci].type" aria-label="Child link type" class="text-sm">
                                        <option value="custom">Custom URL</option>
                                        <option value="page">CMS page</option>
                                        <option value="category">Category</option>
                                        <option value="collection">Collection</option>
                                    </select>
                                    <template x-if="linkOptions(items[i].children[ci].type)">
                                        <select x-model="items[i].children[ci].link" aria-label="Child link target" class="text-sm">
                                            <option value="">Select…</option>
                                            <template x-for="option in linkOptions(items[i].children[ci].type)">
                                                <option x-bind:value="items[i].children[ci].type === 'page' ? option.slug : option.id"
                                                        x-text="items[i].children[ci].type === 'page' ? option.title : option.name">
                                                </option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="! linkOptions(items[i].children[ci].type)">
                                        <input type="text" x-model="items[i].children[ci].link"
                                               placeholder="URL" aria-label="Child custom URL" class="text-sm">
                                    </template>
                                    <div class="flex items-center gap-2 pb-1">
                                        <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                                            <input type="checkbox" x-model="items[i].children[ci].is_visible"> Visible
                                        </label>
                                        <button type="button" x-on:click="move(items[i].children, ci, 'up')"
                                                class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move child up"><x-admin.icon name="chevron-up" :size="12" /></button>
                                        <button type="button" x-on:click="move(items[i].children, ci, 'down')"
                                                class="p-1 rounded text-muted hover:text-charcoal hover:bg-ivory-alt"
                                                aria-label="Move child down"><x-admin.icon name="chevron-down" :size="12" /></button>
                                        <button type="button" x-on:click="items[i].children.splice(ci, 1)"
                                                class="text-xs text-danger hover:underline" aria-label="Remove child">×</button>
                                    </div>
                                </div>
                            </template>

                            <button type="button" x-on:click="addChild(items[i])"
                                    class="text-xs text-champagne-dark hover:underline">+ Add dropdown item</button>
                        </div>
                    @endcan
                </div>
            </template>
        </div>

        <button type="button" x-on:click="items.push(newItem())"
                class="btn-outline btn-sm mt-4">+ Add menu item</button>

        <div class="flex gap-2 mt-5">
            <x-admin.button type="submit" variant="gold" icon="save">{{ $isEdit ? 'Save menu' : 'Create menu' }}</x-admin.button>
            <a href="{{ route('admin.menus.index') }}" class="btn-ghost btn-sm">Back</a>
        </div>
    </form>
</x-admin.card>
