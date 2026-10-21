@props(['course', 'plan', 'offered' => false, 'year' => null])

{{-- One course row in the offering toggle grid: submitting the form adds the
     course to (or removes it from) the active term's offerings. --}}
<form method="POST" action="{{ route('advisor.offering.toggle') }}" data-scroll-keep>
    @csrf
    <input type="hidden" name="course_id" value="{{ $course->id }}">
    <input type="hidden" name="course_plan_id" value="{{ $plan->id }}">
    @if ($year)
        <input type="hidden" name="year" value="{{ $year }}">
    @endif
    <button type="submit"
        class="flex w-full items-center gap-3 rounded-2xl border p-3.5 text-left transition
            {{ $offered ? 'border-emerald-300 bg-emerald-50/70' : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/40' }}">
        <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition {{ $offered ? 'bg-emerald-500' : 'bg-slate-300' }}">
            <span class="absolute size-3.5 rounded-full bg-white shadow transition {{ $offered ? 'translate-x-4' : 'translate-x-1' }}"></span>
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold text-slate-800">
                {{ $course->code }} · {{ $course->name }}
            </span>
            <span class="block text-xs text-slate-400">
                {{-- Elective courses carry no term of their own (semester 0). --}}
                @if ((int) $course->semester >= 1)
                    Sem {{ $course->semester }} ·
                @endif
                {{ $course->credits }} cr
                · {{ $course->courseType?->name ?? 'Unset' }}
            </span>
        </span>
        <x-badge :tone="$offered ? 'emerald' : 'slate'">{{ $offered ? 'Offered' : 'Off' }}</x-badge>
    </button>
</form>
