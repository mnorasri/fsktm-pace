<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-field name="programme_code" label="Programme code" placeholder="e.g. BIM" required />
    <x-field name="programme_name" label="Programme name" placeholder="e.g. Bachelor of Information Technology" required />

    <label class="block">
        <span class="label">Department</span>
        <select name="department_code" class="select" required>
            <option value="">Select a department…</option>
            @foreach ($departments as $department)
                <option value="{{ $department->code }}" @selected(old('department_code') === $department->code)>
                    {{ $department->code }} — {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <x-field name="duration_years" label="Duration (years)" type="number" step="0.5" min="1" max="8" placeholder="4" />
    <x-field name="total_credits" label="Total credits" type="number" min="1" max="300" placeholder="120" />
</div>
<p class="text-xs text-slate-400">Duration and total credits are optional but keep the study plan grid's year columns accurate.</p>
