<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-field name="course_code" label="Course code" placeholder="e.g. BIT 10103" required />
    <x-field name="credit_hours" label="Credit hours" type="number" min="1" max="20" placeholder="3" required />

    <x-field name="course_name_en" label="Course name (English)" placeholder="e.g. Fundamentals of Programming" required />
    <x-field name="course_name_my" label="Course name (Malay)" placeholder="e.g. Asas Pengaturcaraan" />

    <label class="block">
        <span class="label">Course type</span>
        <select name="course_type_code" class="select">
            <option value="">No course type</option>
            @foreach ($courseTypes as $type)
                <option value="{{ $type->code }}" @selected(old('course_type_code') === $type->code)>{{ $type->code }} — {{ $type->name }}</option>
            @endforeach
        </select>
        @error('course_type_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <x-field name="course_load" label="Course load" type="number" step="0.01" min="0" placeholder="e.g. 3" />

    <label class="block">
        <span class="label">SC code</span>
        <select name="sc_code" class="select">
            <option value="">No SC mapping</option>
            @foreach ($mqfs as $sc)
                <option value="{{ $sc->code }}" @selected(old('sc_code') === $sc->code)>{{ $sc->code }} — {{ $sc->name }}</option>
            @endforeach
        </select>
        @error('sc_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <label class="block">
        <span class="label">VBE EPT code</span>
        <select name="vbe_ept_code" class="select">
            <option value="">No VBE EPT mapping</option>
            @foreach ($vbes as $vbe)
                <option value="{{ $vbe->code }}" @selected(old('vbe_ept_code') === $vbe->code)>{{ $vbe->code }} — {{ $vbe->name }}</option>
            @endforeach
        </select>
        @error('vbe_ept_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <label class="block">
        <span class="label">SDG code</span>
        <select name="sdg_code" class="select">
            <option value="">No SDG mapping</option>
            @foreach ($sdgs as $sdg)
                <option value="{{ $sdg->code }}" @selected(old('sdg_code') === $sdg->code)>{{ $sdg->code }} — {{ $sdg->name }}</option>
            @endforeach
        </select>
        @error('sdg_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <label class="block sm:col-span-2">
        <span class="label">Pre-requisite</span>
        <textarea name="pre_requisite" rows="2" class="input" placeholder="Course codes, separated by commas">{{ old('pre_requisite') }}</textarea>
        @error('pre_requisite')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>
</div>
