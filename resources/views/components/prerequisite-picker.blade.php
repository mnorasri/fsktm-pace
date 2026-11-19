@props(['courses'])

@php
    $previousPrerequisites = old('pre_requisite', '');
    $selectedCodes = old('prerequisite_codes', array_values(array_filter(array_map('trim', explode(',', is_string($previousPrerequisites) ? $previousPrerequisites : '')))));
    $selectedCodes = is_array($selectedCodes) ? array_values(array_filter($selectedCodes, 'is_string')) : [];
@endphp

<div {{ $attributes->class(['min-w-0']) }}>
    <span class="label">Pre-requisite</span>
    <input type="hidden" name="prerequisite_picker" value="1">
    <select name="pre_requisite" data-prerequisite-picker class="select" aria-label="Choose prerequisite courses">
        <option value="">No prerequisite</option>
        @foreach ($courses as $option)
            <option value="{{ $option->course_code }}" @selected(($selectedCodes[0] ?? '') === $option->course_code)>
                {{ $option->course_code }} — {{ $option->course_name_en }}
            </option>
        @endforeach
    </select>
    @error('prerequisite_codes')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error('prerequisite_codes.*')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
    @error('pre_requisite')
        <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>
