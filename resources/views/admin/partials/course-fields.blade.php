@props(['programs', 'courseTypes', 'mqfs', 'vbes', 'sdgs', 'plans'])

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="label">Course Code</span>
        <input type="text" name="code" class="input" required placeholder="SCSJ1013">
    </label>
    <label class="block">
        <span class="label">Course Name</span>
        <input type="text" name="name" class="input" required placeholder="Programming Technique I">
    </label>

    <label class="block">
        <span class="label">Course Type</span>
        <select name="course_type_id" class="select">
            <option value="">— None —</option>
            @foreach ($courseTypes as $type)
                <option value="{{ $type->id }}">{{ $type->code }} — {{ $type->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Credits</span>
        <input type="number" name="credits" class="input" min="1" max="10" value="3" required>
    </label>

    <label class="block">
        <span class="label">Course Load</span>
        <input type="number" name="course_load" class="input" min="0" step="0.5" placeholder="e.g. 120">
    </label>
    <label class="block">
        <span class="label">Semester</span>
        <select name="semester" class="select">
            <option value="">— None —</option>
            @foreach (range(1, 10) as $sem)
                <option value="{{ $sem }}">Semester {{ $sem }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="label">Programme Code</span>
        <select name="program_id" class="select">
            <option value="">— None —</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}">{{ $program->code }} — {{ $program->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Plan Code</span>
        <select name="course_plan_id" class="select">
            <option value="">— None —</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->code }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="label">Prerequisites</span>
        <input type="text" name="prerequisites" class="input" placeholder="SCSJ1013, SSCM1033">
    </label>
    <label class="block">
        <span class="label">SC</span>
        <select name="mqf_sc_id" class="select">
            <option value="">— None —</option>
            @foreach ($mqfs as $mqf)
                <option value="{{ $mqf->id }}">{{ $mqf->code }} — {{ $mqf->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block">
        <span class="label">VBE</span>
        <select name="vbe_id" class="select">
            <option value="">— None —</option>
            @foreach ($vbes as $vbe)
                <option value="{{ $vbe->id }}">{{ $vbe->code }} — {{ $vbe->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">SDG</span>
        <select name="sdg_id" class="select">
            <option value="">— None —</option>
            @foreach ($sdgs as $sdg)
                <option value="{{ $sdg->id }}">{{ $sdg->code }} — {{ $sdg->name }}</option>
            @endforeach
        </select>
    </label>

    <label class="block sm:col-span-2">
        <span class="label">Alternative group</span>
        <input type="text" name="alternative_group" class="input" maxlength="60" placeholder="e.g. Pengajian">
        <span class="mt-1 block text-xs text-slate-400">
            Courses sharing a group are mutually exclusive — a study plan carries exactly one of them
            (Pengajian Islam <span class="font-semibold">or</span> Pengajian Moral). Leave blank for a normal course.
        </span>
    </label>
</div>
