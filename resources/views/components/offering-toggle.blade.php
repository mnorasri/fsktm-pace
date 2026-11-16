@props(['course', 'plan', 'offered' => false, 'year' => null, 'windowOpen' => true])

@php
    // The administrator sets a cutoff date: until then a Head of Department may
    // add or remove courses. After it only an administrator can change offerings.
    $isAdmin = auth()->user()?->isAdmin() ?? false;
    $locked = ! $windowOpen && ! $isAdmin;
@endphp

{{-- One course row in the offering toggle grid: submitting the form adds the
     course to (or removes it from) the active term's offerings. --}}
<form method="POST" action="{{ route('advisor.offering.toggle') }}" data-scroll-keep
    @if ($offered)
        onsubmit="return confirm('Remove {{ $course->code }} from the offering grid? Its student registrations, including approved ones, will be deleted.');"
    @endif>
    @csrf
    <input type="hidden" name="course_id" value="{{ $course->id }}">
    <input type="hidden" name="course_plan_id" value="{{ $plan->id }}">
    @if ($year)
        <input type="hidden" name="year" value="{{ $year }}">
    @endif
    <button type="submit" @disabled($locked)
        @if ($locked) title="Course offerings are closed — only an administrator can change them now." @endif
        class="flex w-full items-center gap-3 rounded-2xl border p-3.5 text-left transition
            {{ $offered ? 'border-emerald-300 bg-emerald-50/70' : 'border-slate-200 bg-white hover:border-brand-300 hover:bg-brand-50/40' }}
            {{ $locked ? 'cursor-not-allowed' : '' }}">
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
        <span class="flex shrink-0 flex-col items-end gap-1">
            <x-badge :tone="$offered ? 'emerald' : 'slate'">{{ $offered ? 'Offered' : 'Off' }}</x-badge>
            @if ($locked)
                <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Closed</span>
            @endif
        </span>
    </button>
</form>
