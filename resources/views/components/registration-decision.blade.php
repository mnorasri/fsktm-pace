@props(['student', 'courses', 'credits'])

<section class="mt-4 space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Registration decision</h3>
            <p class="mt-1 text-xs text-slate-500">Review the courses and prerequisite indicators above, then choose one action.</p>
        </div>
        <x-badge tone="slate">{{ $courses }} course(s) · {{ $credits }} credits</x-badge>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="flex flex-col items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
            <h4 class="text-sm font-semibold text-emerald-900">Approve registration</h4>
            <p class="text-xs text-emerald-800">Approve the whole registration and confirm all pending courses for this term.</p>
            <form method="POST" action="{{ route('advisor.registrations.batch.approve', $student) }}" class="mt-auto" data-scroll-keep>
                @csrf
                <button class="btn-success btn-sm"><x-icon name="check" class="size-3.5" /> Approve registration</button>
            </form>
        </div>
        <form method="POST" action="{{ route('advisor.registrations.batch.reject', $student) }}"
            class="flex flex-col items-start gap-3 rounded-xl border border-rose-200 bg-rose-50/50 p-4" data-scroll-keep>
            @csrf
            <input type="hidden" name="review_student_id" value="{{ $student->id }}">
            <h4 class="text-sm font-semibold text-rose-900">Reject registration</h4>
            <p class="text-xs text-rose-800">Reject the whole registration submission, not individual courses. Explain what needs changing so the student can revise and resubmit. Already approved courses stay confirmed.</p>
            <label class="block w-full">
                <span class="label">Reason for rejecting <span class="text-rose-600">(required)</span></span>
                <textarea name="note" rows="3" maxlength="2000" required class="input"
                    placeholder="Explain why this registration is rejected.">{{ (string) old('review_student_id') === (string) $student->id ? old('note') : '' }}</textarea>
            </label>
            <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /> Reject registration</button>
        </form>
    </div>
</section>
