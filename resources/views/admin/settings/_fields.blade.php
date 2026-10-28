@foreach ($fields as $field)
    @php
        $name = "settings[$module][{$field['key']}]";
        $id = 'setting-' . $field['key'];
        $type = $field['type'] ?? 'text';
        $hintId = $id.'-hint';
    @endphp

    @if ($module === 'branding' && $field['key'] === 'store_logo_url')
        <div class="md:col-span-2 rounded-xl border border-charcoal/10 bg-[#fffdf9] p-4 sm:p-5"
             x-data="{ preview: @js(\App\Support\Branding::logoUrl()) }">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="flex h-24 w-40 shrink-0 items-center justify-center rounded-lg border border-charcoal/10 bg-white p-3">
                    <img :src="preview" alt="Store logo preview" class="max-h-full max-w-full object-contain">
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">Store logo</p>
                    <p class="mt-1 text-sm text-muted">This logo appears in the storefront header and footer, and the admin top bar. Transparent PNG or WebP works best.</p>
                    <label for="setting-logo-file" class="mt-3">Upload logo image</label>
                    <input type="file" id="setting-logo-file" name="logo_file" accept="image/jpeg,image/png,image/webp,image/gif"
                           class="block w-full rounded-lg border border-dashed border-[#9c744a]/40 bg-white p-2.5 text-sm"
                           @change="if ($event.target.files[0]) preview = URL.createObjectURL($event.target.files[0])">
                    <p class="mt-1 text-xs text-muted">JPG, PNG, WebP or GIF · 5 MB maximum. Uploading a file replaces the logo path below.</p>
                    @error('logo_file') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>
            <x-admin.field :name="$name" :label="'Logo URL or storage path'" :id="$id"
                           hint="Use a site image path, a public storage path, or an HTTPS image URL. An uploaded logo takes precedence when saved.">
                <x-admin.input :name="$name" :id="$id" :value="$field['value']" type="text"
                               placeholder="/images/logo.png or branding/logo.webp"
                               @input="if ($event.target.value.startsWith('/images/') || $event.target.value.startsWith('/storage/') || $event.target.value.startsWith('https://') || $event.target.value.startsWith('http://')) preview = $event.target.value" />
            </x-admin.field>
        </div>
    @elseif ($type === 'boolean')
        <div>
            <div class="flex items-center">
                <input type="hidden" name="{{ $name }}" value="0">
                <x-admin.checkbox :name="$name" :id="$id" :label="$field['label']" :checked="(bool) $field['value']"
                                  :aria-describedby="!empty($field['help']) ? $hintId : null" />
            </div>
            @isset($field['help'])
                <p id="{{ $hintId }}" class="text-xs text-muted mt-1 ps-7">{{ $field['help'] }}</p>
            @endisset
        </div>
    @else
        <x-admin.field :name="$name" :label="$field['label']" :id="$id" :hint="$field['help'] ?? null">
            @if ($type === 'textarea')
                <x-admin.textarea :name="$name" :id="$id" :value="$field['value']" :rows="4"
                                  :describedby="!empty($field['help']) ? $hintId : null" />
            @else
                <x-admin.input :name="$name" :id="$id" :value="$field['value']"
                               :type="in_array($type, ['email', 'number'], true) ? $type : 'text'"
                               :describedby="!empty($field['help']) ? $hintId : null" />
            @endif
        </x-admin.field>
    @endif
@endforeach
