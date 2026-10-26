@php
    $tone = match ($row['category']) {
        'elective' => 'brand',
        'university' => 'sky',
        default => 'slate',
    };
@endphp

{{-- One course row in a term of the study plan. Placed rows (plan_courses) can
     be moved or removed here; courses that are only attached through the course
     directory can be pinned to the term ("Place") or detached from the plan. --}}
<div class="flex items-start gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5">
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-800">{{ $row['code'] }}</p>
        <p class="truncate text-xs text-slate-400">{{ $row['name'] }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-1">
            <x-badge :tone="$tone">{{ ucfirst($row['category']) }}</x-badge>
            <x-badge tone="slate">{{ $row['credits'] }} cr</x-badge>
            @unless ($row['slotted'])
                <x-badge tone="amber" title="Placed by the course's own curriculum semester — use Place to fix it to a term">Course semester</x-badge>
            @endunless
            @if ($row['slotted'] && $row['plan_course']?->placeholder_key)
                <x-badge tone="amber">Elective slot</x-badge>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-1">
        @if ($row['slotted'])
            <button type="button" class="btn-ghost btn-sm" data-edit="edit-plan-row"
                data-action="{{ route('advisor.plans.courses.update', [$plan, $row['plan_course']]) }}"
                data-title="{{ $row['code'] }}"
                data-field-year="{{ $row['year'] }}"
                data-field-semester="{{ $row['semester'] }}"
                data-field-category="{{ $row['category'] }}"
                data-field-credits="{{ $row['credits'] }}">
                <x-icon name="cog" class="size-3.5" /> Edit
            </button>
            <form method="POST" action="{{ route('advisor.plans.courses.destroy', [$plan, $row['plan_course']]) }}"
                data-confirm="Remove {{ $row['code'] }} from the {{ $plan->code }} timetable?">
                @csrf
                @method('DELETE')
                <button class="btn-danger btn-sm" title="Remove from this term">
                    <x-icon name="x" class="size-3.5" />
                </button>
            </form>
        @else
            <button type="button" class="btn-ghost btn-sm" data-edit="add-plan-course"
                data-title="Place {{ $row['code'] }} in the plan"
                data-field-course_id="{{ $row['course']->id }}"
                data-field-year="{{ $row['year'] }}"
                data-field-semester="{{ $row['semester'] }}"
                data-field-category="{{ $row['category'] }}"
                data-field-credits="{{ $row['credits'] }}">
                <x-icon name="calendar" class="size-3.5" /> Place
            </button>
            <form method="POST" action="{{ route('advisor.plans.attached.destroy', [$plan, $row['course']]) }}"
                data-confirm="Remove {{ $row['code'] }} from {{ $plan->code }}? The course itself stays in the course directory.">
                @csrf
                @method('DELETE')
                <button class="btn-danger btn-sm" title="Remove from this plan">
                    <x-icon name="x" class="size-3.5" />
                </button>
            </form>
        @endif
    </div>
</div>
