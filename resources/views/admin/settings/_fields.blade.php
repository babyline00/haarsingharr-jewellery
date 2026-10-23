@foreach ($fields as $field)
    <div>
        @if (($field['type'] ?? 'text') === 'boolean')
            <input type="hidden" name="settings[{{ $module }}][{{ $field['key'] }}]" value="0">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="settings[{{ $module }}][{{ $field['key'] }}]" value="1" @checked((bool) $field['value'])>
                {{ $field['label'] }}
            </label>
        @elseif (($field['type'] ?? 'text') === 'file')
            <label for="setting-{{ $field['key'] }}">{{ $field['label'] }}</label>
            <input type="file"
                   id="setting-{{ $field['key'] }}"
                   name="settings[{{ $module }}][{{ $field['key'] }}]"
                   accept="image/png,image/jpeg,image/webp,image/gif"
                   class="block w-full text-sm text-muted file:mr-4 file:rounded-md file:border-0 file:bg-champagne/20 file:px-4 file:py-2 file:text-sm file:font-medium file:text-charcoal hover:file:bg-champagne/30">
            @if (! empty($field['preview']) && ($previewValue = \App\Models\StoreSetting::get($field['preview'])))
                <div class="mt-3 flex items-center gap-3 rounded-md border border-charcoal/10 bg-ivory-alt/60 p-2">
                    <img src="{{ \App\Support\Brand::resolve($previewValue) }}" alt="Current image" class="h-12 w-auto max-w-[160px] object-contain">
                    <span class="text-xs text-muted break-all">{{ $previewValue }}</span>
                </div>
            @endif
        @elseif (($field['type'] ?? 'text') === 'select')
            <label for="setting-{{ $field['key'] }}">{{ $field['label'] }}</label>
            <select id="setting-{{ $field['key'] }}" name="settings[{{ $module }}][{{ $field['key'] }}]">
                @foreach (($field['options'] ?? []) as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $field['value'] === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @else
            <label for="setting-{{ $field['key'] }}">{{ $field['label'] }}</label>
            @if (($field['type'] ?? 'text') === 'textarea')
                <textarea id="setting-{{ $field['key'] }}" name="settings[{{ $module }}][{{ $field['key'] }}]" rows="4">{{ $field['value'] }}</textarea>
            @else
                <input
                    type="{{ in_array($field['type'] ?? 'text', ['email', 'number'], true) ? $field['type'] : 'text' }}"
                    id="setting-{{ $field['key'] }}"
                    name="settings[{{ $module }}][{{ $field['key'] }}]"
                    value="{{ $field['value'] }}"
                >
            @endif
        @endif

        @if (! empty($field['help']))
            <p class="mt-1 text-xs text-muted">{{ $field['help'] }}</p>
        @endif
    </div>
@endforeach
