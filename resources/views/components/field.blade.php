@props(['name', 'label' => null, 'value' => null, 'type' => 'text', 'required' => false, 'placeholder' => null])

<label class="block">
    @if ($label)
        <span class="label">{{ $label }}</span>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @if ($required) required @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'input']) }}
    >
    @error($name)
        <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
    @enderror
</label>
