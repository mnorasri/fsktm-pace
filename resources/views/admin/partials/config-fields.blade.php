@foreach ($fields as $field)
    <label class="block {{ ($field['full'] ?? false) ? 'sm:col-span-2' : '' }}">
        <span class="label">{{ $field['label'] }}</span>

        @if (($field['type'] ?? 'text') === 'select')
            <select name="{{ $field['name'] }}" class="select" @if ($field['required'] ?? false) required @endif>
                <option value="">— None —</option>
                @foreach ($field['options'] ?? [] as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
        @elseif (($field['type'] ?? 'text') === 'textarea')
            <textarea name="{{ $field['name'] }}" rows="2" class="input">{{ old($field['name']) }}</textarea>
        @else
            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" value="{{ old($field['name']) }}"
                class="input" @if ($field['required'] ?? false) required @endif
                @isset($field['step']) step="{{ $field['step'] }}" @endisset
                @isset($field['min']) min="{{ $field['min'] }}" @endisset
                @isset($field['max']) max="{{ $field['max'] }}" @endisset>
        @endif
    </label>
@endforeach
