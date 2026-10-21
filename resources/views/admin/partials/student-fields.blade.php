<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <label class="block">
        <span class="label">Matric No.</span>
        <input type="text" name="student_id" class="input" placeholder="e.g. A210001">
    </label>
    <label class="block">
        <span class="label">Student Phone</span>
        <input type="text" name="phone" class="input" placeholder="e.g. 0123456789">
    </label>
    <label class="block">
        <span class="label">Student Name</span>
        <input type="text" name="name" class="input" required>
    </label>
    <label class="block">
        <span class="label">Student Email</span>
        <input type="email" name="email" class="input" required>
    </label>
    <label class="block">
        <span class="label">Programme</span>
        <select name="program_id" class="select">
            <option value="">— None —</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}">{{ $program->code }} — {{ $program->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="block">
        <span class="label">Curriculum Plan</span>
        <select name="plan_id" class="select">
            <option value="">— None —</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->code }}</option>
            @endforeach
        </select>
    </label>
    <label class="block sm:col-span-2">
        <span class="label">PAK Advisor (Lecturer ID)</span>
        <select name="advisor_id" class="select">
            <option value="">— None —</option>
            @foreach ($advisors as $advisor)
                <option value="{{ $advisor->id }}">
                    {{ $advisor->name }}{{ $advisor->staff_id ? ' — '.$advisor->staff_id : '' }}
                </option>
            @endforeach
        </select>
    </label>
    @if ($showStatus ?? false)
        <label class="block sm:col-span-2">
            <span class="label">Status</span>
            <select name="status" class="select">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label>
    @endif
</div>
