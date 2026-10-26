<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="label">Study plan</span>
        <select name="study_plan_code" class="select" required>
            <option value="">Select a study plan…</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->code }}" @selected(old('study_plan_code') === $plan->code)>
                    {{ $plan->code }} — {{ $plan->name }}
                </option>
            @endforeach
        </select>
        @error('study_plan_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <label class="block">
        <span class="label">Semester</span>
        <input type="number" name="semester" min="1" max="16" value="{{ old('semester') }}" placeholder="e.g. 1" class="input" required>
        @error('semester')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>

    <label class="block sm:col-span-2">
        <span class="label">Course</span>
        <select name="course_code" class="select" required>
            <option value="">Select a course…</option>
            @foreach ($courses as $course)
                <option value="{{ $course->code }}" @selected(old('course_code') === $course->code)>
                    {{ $course->course_code }} — {{ $course->course_name_en }} ({{ $course->credit_hours }} cr)
                </option>
            @endforeach
        </select>
        @error('course_code')
            <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>
        @enderror
    </label>
</div>
<p class="text-xs text-slate-400">Semester 1 is the first semester of the programme; each year holds two semesters.</p>
