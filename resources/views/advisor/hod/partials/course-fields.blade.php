@props(['programs', 'courseTypes', 'mqfs', 'vbes', 'sdgs', 'plans'])

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="label">Course Code</span>
        <input type="text" name="course_code" value="{{ old('course_code') }}" class="input" required placeholder="SCSJ1013">
    </label>
    <label class="block">
        <span class="label">Credit Hours</span>
        <input type="number" name="credit_hours" value="{{ old('credit_hours', 3) }}" class="input" min="1" max="20" required>
    </label>

    <label class="block">
        <span class="label">Course Name (English)</span>
        <input type="text" name="course_name_en" value="{{ old('course_name_en') }}" class="input" required placeholder="Programming Technique I">
    </label>
    <label class="block">
        <span class="label">Course Name (Malay)</span>
        <input type="text" name="course_name_my" value="{{ old('course_name_my') }}" class="input" placeholder="Teknik Pengaturcaraan I">
    </label>

    <label class="block">
        <span class="label">Course Type</span>
        <select name="course_type_code" class="select">
            <option value="">— None —</option>
            @foreach ($courseTypes as $type)
                <option value="{{ $type->code }}" @selected(old('course_type_code') === $type->code)>{{ $type->code }} — {{ $type->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Course Load</span>
        <input type="number" name="course_load" value="{{ old('course_load') }}" class="input" min="0" step="0.5" placeholder="e.g. 120">
    </label>

    <label class="block">
        <span class="label">Semester</span>
        <select name="semester" class="select">
            <option value="">— None —</option>
            @foreach (range(1, 10) as $sem)
                <option value="{{ $sem }}" @selected((string) old('semester') === (string) $sem)>Semester {{ $sem }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Programme</span>
        <select name="program_id" class="select">
            <option value="">— None —</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected((string) old('program_id') === (string) $program->id)>{{ $program->code }} — {{ $program->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="label">Plan Code</span>
        <select name="course_plan_id" class="select">
            <option value="">— None —</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((string) old('course_plan_id') === (string) $plan->id)>{{ $plan->code }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Pre-requisite</span>
        <input type="text" name="pre_requisite" value="{{ old('pre_requisite') }}" class="input" placeholder="SCSJ1013, SSCM1033">
    </label>

    <label class="block">
        <span class="label">SC</span>
        <select name="sc_code" class="select">
            <option value="">— None —</option>
            @foreach ($mqfs as $mqf)
                <option value="{{ $mqf->code }}" @selected(old('sc_code') === $mqf->code)>{{ $mqf->code }} — {{ $mqf->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">VBE EPT</span>
        <select name="vbe_ept_code" class="select">
            <option value="">— None —</option>
            @foreach ($vbes as $vbe)
                <option value="{{ $vbe->code }}" @selected(old('vbe_ept_code') === $vbe->code)>{{ $vbe->code }} — {{ $vbe->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="label">SDG</span>
        <select name="sdg_code" class="select">
            <option value="">— None —</option>
            @foreach ($sdgs as $sdg)
                <option value="{{ $sdg->code }}" @selected(old('sdg_code') === $sdg->code)>{{ $sdg->code }} — {{ $sdg->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Alternative group</span>
        <input type="text" name="alternative_group" value="{{ old('alternative_group') }}" class="input" maxlength="60" placeholder="e.g. Pengajian">
        <span class="mt-1 block text-xs text-slate-400">
            Courses sharing a group are mutually exclusive — a study plan carries exactly one of them
            (Pengajian Islam <span class="font-semibold">or</span> Pengajian Moral). Leave blank for a normal course.
        </span>
    </label>
</div>
