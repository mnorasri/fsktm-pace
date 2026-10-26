<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-field name="study_plan_code" label="Study plan code" placeholder="e.g. BIM20252026-LOCAL" required />
    <x-field name="study_plan_name" label="Study plan name" placeholder="e.g. Bachelor of IT — Local" required />

    <label class="block">
        <span class="label">Programme</span>
        <select name="programme_code" class="select" required>
            <option value="">Select a programme…</option>
            @foreach ($programs as $program)
                <option value="{{ $program->code }}" @selected(old('programme_code') === $program->code)>
                    {{ $program->code }} — {{ $program->name }}
                </option>
            @endforeach
        </select>
        @error('programme_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <x-field name="version" label="Version" placeholder="e.g. 2025/2026" />

    <x-field name="total_semester" label="Total semesters" type="number" min="1" max="16" placeholder="e.g. 8" />
</div>
<p class="text-xs text-slate-400">Total semesters caps the semester number accepted when courses are placed or imported onto this plan.</p>
