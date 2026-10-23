@foreach ($fields as $field)
    <div>
        @if (($field['type'] ?? 'text') === 'boolean')
            <input type="hidden" name="settings[{{ $module }}][{{ $field['key'] }}]" value="0">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="settings[{{ $module }}][{{ $field['key'] }}]" value="1" @checked((bool) $field['value'])>
                {{ $field['label'] }}
            </label>
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
    </div>
@endforeach
