@extends('layouts.app')

@section('title', 'Study Plan')

@php
    $statusTone = ['PLANNED' => 'sky', 'COMPLETED' => 'emerald', 'EXEMPTED' => 'amber'];
@endphp

@section('content')
    <x-page-header title="Interactive Study Plan"
        subtitle="{{ $student->program?->name }} · {{ $student->plan?->name }} ({{ $student->plan?->version }})">
        <x-slot:actions>
            <button type="button" data-modal-open="add-course-modal" class="btn-ghost">
                <x-icon name="plus" class="size-4" /> Add course
            </button>
            <a href="{{ route('student.slip') }}" class="btn-ghost">
                <x-icon name="printer" class="size-4" /> Slip
            </a>
            @if (in_array($student->plan_status, ['draft', 'rejected']))
                <form method="POST" action="{{ route('student.study-plan.submit') }}">
                    @csrf
                    <button class="btn-primary">
                        <x-icon name="upload" class="size-4" /> Submit for approval
                    </button>
                </form>
            @else
                <x-badge tone="{{ $student->plan_status === 'approved' ? 'emerald' : 'amber' }}" dot>
                    {{ ucfirst($student->plan_status) }}
                </x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Summary strip --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Required" :value="$credits['required']" icon="academic-cap" tone="brand" />
        <x-stat-card label="Earned" :value="$credits['earned']" icon="check" tone="emerald" />
        <x-stat-card label="Planned" :value="$credits['planned']" icon="calendar" tone="sky" />
        <x-stat-card label="Remaining" :value="$credits['remaining']" icon="chart" tone="amber" />
    </div>

    @if ($pendingElectives > 0)
        <div class="mt-5 flex items-start gap-3 rounded-3xl border border-brand-200 bg-brand-50 p-5">
            <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-brand-100 text-brand-600">
                <x-icon name="sparkles" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-bold text-brand-800">{{ $pendingElectives }} elective slot(s) still need a course</p>
                <p class="text-xs text-brand-700">Pick a course from the dropdown on each <span class="font-semibold">Elective Option</span> row — the <a href="#elective-courses" class="font-semibold underline decoration-brand-300 underline-offset-2 hover:text-brand-800">Elective Course</a> panel below the last year lists every option.</p>
            </div>
        </div>
    @endif

    @if (\count($warnings) > 0)
        <div class="mt-5 flex items-start gap-3 rounded-3xl border border-amber-200 bg-amber-50 p-5">
            <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600">
                <x-icon name="warning" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-bold text-amber-800">{{ \count($warnings) }} prerequisite violation(s) detected</p>
                <p class="text-xs text-amber-700">A prerequisite is planned in the same or a later term. Rows are flagged below.</p>
            </div>
        </div>
    @endif

    {{-- Legend --}}
    <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-sky-500"></span> Planned</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Completed</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-500"></span> Exempted</span>
        <span class="flex items-center gap-1.5"><x-icon name="warning" class="size-4 text-amber-500" /> Prerequisite placed late</span>
    </div>

    {{-- Study grid — one row per year: odd semester, (special semester), even semester --}}
    @php
        $shortSemester = [1 => 'Sem 1', 2 => 'Sem 2', 3 => 'Special'];
        $hasSpecial = in_array(3, $semesters, true);
    @endphp

    <div class="mt-5 space-y-7">
        @foreach ($years as $year => $terms)
            <section id="year-{{ $year }}" class="scroll-mt-24 space-y-4">
                <div class="flex items-center gap-3 pt-2">
                    <span class="grid size-9 place-items-center rounded-2xl bg-slate-900 text-xs font-bold text-white">Y{{ $year }}</span>
                    <h2 class="text-lg font-bold text-slate-800">Year {{ $year }}</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 {{ $hasSpecial ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                    @foreach ($semesters as $semester)
                        @php
                            $term = $terms[$semester];
                        @endphp
                        <x-card pad
                            title="{{ $semesterLabels[$semester] }}"
                            subtitle="{{ $term['entries']->count() }} course(s) · {{ $term['credits'] }} credits">
                            @if ($term['entries']->isEmpty())
                                <x-empty-state title="No courses in this term" icon="calendar"
                                    message="Nothing planned for Year {{ $year }} {{ $semesterLabels[$semester] }} yet." />
                            @else
                                <ul class="space-y-2.5">
                                    @foreach ($term['entries'] as $entry)
                                        @php
                                            // Anything without a course is treated as a placeholder
                                            // slot so an incomplete row can never break the page.
                                            $isPlaceholder = $entry->course === null;
                                            $placeholderLabel = $entry->placeholder_key ?: 'Elective slot';
                                            // An "Elective Option" row (type CT07A) carries the
                                            // student's choice of "Elective Course" (CT07).
                                            $isElectiveOption = $entry->isElectiveOption();
                                            $electiveSlot = $isElectiveOption ? $entry->electiveSlotCourse() : null;
                                            $electiveChosen = $entry->hasElectiveChoice();
                                            $late = $warnings[$entry->id] ?? [];
                                            $group = $entry->course?->alternative_group;
                                            $termLabel = $shortSemester[$entry->semester] ?? 'Sem '.$entry->semester;
                                        @endphp
                                        <li class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 transition hover:border-brand-200 hover:bg-white">
                                            @if ($isElectiveOption)
                                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-600">
                                                    <x-icon name="sparkles" class="size-4" />
                                                </span>
                                                <div class="min-w-[15rem] flex-1">
                                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                                        {{ $electiveSlot?->name ?? 'Elective option' }}
                                                        <x-badge tone="brand">Elective Option</x-badge>
                                                        @if ($electiveChosen)
                                                            <span class="font-normal text-slate-500">{{ $entry->course->code }} · {{ $entry->course->name }}</span>
                                                        @else
                                                            <x-badge tone="amber">No course chosen</x-badge>
                                                        @endif
                                                    </p>
                                                    <p class="text-xs text-slate-400">
                                                        @if ($electiveChosen)
                                                            {{ $entry->course->credits }} credits
                                                            @if ($entry->course->prerequisites)
                                                                · Prereq: {{ $entry->course->prerequisites }}
                                                            @endif
                                                        @else
                                                            Choose one of the programme's elective courses — {{ $electiveCourses->count() }} available
                                                        @endif
                                                    </p>
                                                </div>
                                            @elseif ($isPlaceholder)
                                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-600">
                                                    <x-icon name="sparkles" class="size-4" />
                                                </span>
                                                <div class="min-w-[15rem] flex-1">
                                                    <p class="text-sm font-semibold text-slate-700">{{ $placeholderLabel }}</p>
                                                    <p class="text-xs text-slate-400">Elective placeholder — replace with a real course</p>
                                                </div>
                                            @else
                                                <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-slate-400 shadow-sm">
                                                    <x-icon name="book" class="size-4" />
                                                </span>
                                                <div class="min-w-[15rem] flex-1">
                                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                                        {{ $entry->course->code }} · {{ $entry->course->name }}
                                                        @if ($entry->placeholder_key)
                                                            <x-badge tone="brand">Elective</x-badge>
                                                        @endif
                                                        @if ($group)
                                                            <x-badge tone="brand">{{ $group }}</x-badge>
                                                        @endif
                                                        @if (! empty($late))
                                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                                <x-icon name="warning" class="size-3" /> Prereq misplaced
                                                            </span>
                                                        @endif
                                                    </p>
                                                    <p class="text-xs text-slate-400">
                                                        {{ $entry->course->credits }} credits
                                                        @if ($entry->course->prerequisites)
                                                            · Prereq: {{ $entry->course->prerequisites }}
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif

                                            <div class="ml-auto flex flex-wrap items-center gap-2">
                                                @if ($group)
                                                    {{-- Either/or group: one option only. --}}
                                                    <div class="flex items-center gap-0.5 rounded-xl border border-slate-200 bg-white p-0.5"
                                                        title="{{ $group }} — choose one, your plan carries a single {{ $group }} course">
                                                        @foreach (($alternativeGroups[$group] ?? collect()) as $option)
                                                            <form method="POST" action="{{ route('student.study-plan.alternative') }}" data-scroll-keep>
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="group" value="{{ $group }}">
                                                                <input type="hidden" name="course_id" value="{{ $option->id }}">
                                                                <button type="submit"
                                                                    title="{{ $option->code }} · {{ $option->name }}"
                                                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold transition {{ (int) $option->id === (int) $entry->course_id ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100' }}">
                                                                    {{ $option->name }}
                                                                </button>
                                                            </form>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if ($isPlaceholder)
                                                    <button type="button"
                                                        data-replace-entry="{{ route('student.study-plan.replace', $entry) }}"
                                                        data-placeholder="{{ $entry->placeholder_key }}"
                                                        class="btn-subtle btn-sm">
                                                        <x-icon name="arrow-path" class="size-3.5" /> Replace
                                                    </button>
                                                @else
                                                    @php
                                                        $movable = $movableSemesters[$entry->id] ?? [$entry->semester];
                                                        $movableTitle = \count($movable) > 1
                                                            ? 'Industrial Training may be placed in an odd or even semester'
                                                            : 'Move to another year — a '.$semesterLabels[$entry->semester].' course stays in '.$semesterLabels[$entry->semester];
                                                    @endphp
                                                    @if ($isElectiveOption)
                                                        {{-- Elective Option: the enrolment choice for this slot. --}}
                                                        <form method="POST" action="{{ route('student.study-plan.elective', $entry) }}" data-scroll-keep>
                                                            @csrf
                                                            @method('PUT')
                                                            <select name="course_id" data-auto-submit
                                                                title="Choose the elective course for {{ $electiveSlot?->name ?? 'this slot' }}"
                                                                class="select w-72 shrink-0 py-1.5 text-xs font-semibold">
                                                                <option value="">— not chosen —</option>
                                                                @foreach ($electiveCourses as $option)
                                                                    @php
                                                                        $usedFor = $electiveChoices[$option->id] ?? null;
                                                                        $isCurrentChoice = (int) $option->id === (int) $entry->course_id;
                                                                    @endphp
                                                                    <option value="{{ $option->id }}" @selected($isCurrentChoice) @disabled($usedFor !== null && ! $isCurrentChoice)>
                                                                        {{ $option->code }} — {{ $option->name }} ({{ $option->credits }} cr){{ $usedFor !== null && ! $isCurrentChoice ? ' · chosen for '.$usedFor : '' }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    @elseif ($entry->status === 'PLANNED')
                                                        {{-- Odd stays odd, even stays even, special stays special:
                                                            only the year changes — except industrial training,
                                                            which may sit in either semester. --}}
                                                        <form method="POST" action="{{ route('student.study-plan.term', $entry) }}" data-scroll-keep>
                                                            @csrf
                                                            @method('PUT')
                                                            <select name="term" data-auto-submit title="{{ $movableTitle }}"
                                                                class="select w-40 shrink-0 py-1.5 text-xs font-semibold">
                                                                @foreach ($yearRange as $optionYear)
                                                                    @foreach ($movable as $optionSemester)
                                                                        <option value="{{ $optionYear }}:{{ $optionSemester }}"
                                                                            @selected((int) $entry->year === $optionYear && (int) $entry->semester === $optionSemester)>
                                                                            Year {{ $optionYear }} · {{ $shortSemester[$optionSemester] ?? 'Sem '.$optionSemester }}
                                                                        </option>
                                                                    @endforeach
                                                                @endforeach
                                                            </select>
                                                        </form>
                                                    @else
                                                        <span class="rounded-lg bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-500 shadow-sm">
                                                            Year {{ $entry->year }} · {{ $termLabel }}
                                                        </span>
                                                    @endif

                                                    <form method="POST" action="{{ route('student.study-plan.status', $entry) }}" data-scroll-keep>
                                                        @csrf
                                                        @method('PUT')
                                                        <select name="status" data-auto-submit
                                                            class="select w-28 shrink-0 py-1.5 text-xs font-semibold">
                                                            @foreach (['PLANNED' => 'Planned', 'COMPLETED' => 'Completed', 'EXEMPTED' => 'Exempted'] as $value => $label)
                                                                <option value="{{ $value }}" @selected($entry->status === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                @endif

                                                <span class="badge-{{ $statusTone[$entry->status] ?? 'slate' }}">{{ $entry->status }}</span>

                                                @unless ($group || $isElectiveOption)
                                                    <form method="POST" action="{{ route('student.study-plan.remove', $entry) }}" data-confirm="Remove this course from your study plan?" data-scroll-keep>
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                            <x-icon name="x" class="size-4" />
                                                        </button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    {{-- Elective Course panel — the plan's elective catalogue, listed after the
         last year. These courses fill the Elective Option slots above. --}}
    @if ($electiveCourses->isNotEmpty())
        <section id="elective-courses" class="mt-7 scroll-mt-24 space-y-4">
            <div class="flex items-center gap-3 pt-2">
                <span class="grid size-9 place-items-center rounded-2xl bg-brand-600 text-white">
                    <x-icon name="sparkles" class="size-4" />
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Elective Course</h2>
                    <p class="text-xs text-slate-500">
                        {{ $electiveCourses->count() }} course(s) · {{ $electiveCourses->sum('credits') }} credits —
                        pick one for each Elective Option row above
                    </p>
                </div>
            </div>

            <x-card>
                <ul class="grid grid-cols-1 gap-2.5 md:grid-cols-2">
                    @foreach ($electiveCourses as $course)
                        @php
                            $chosenFor = $electiveChoices[$course->id] ?? null;
                        @endphp
                        <li class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/60 p-3.5 transition hover:border-brand-200 hover:bg-white">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white text-brand-600 shadow-sm">
                                <x-icon name="book" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                    {{ $course->code }} · {{ $course->name }}
                                    @if ($chosenFor)
                                        <x-badge tone="emerald">Chosen for {{ $chosenFor }}</x-badge>
                                    @else
                                        <x-badge tone="slate">Available</x-badge>
                                    @endif
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ $course->credits }} credits
                                    @if ($course->courseType?->name)
                                        · {{ $course->courseType->name }}
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </section>
    @endif

    {{-- Add course modal --}}
    <x-modal id="add-course-modal" title="Add a course to your plan"
        subtitle="Place a course into any semester of your four-year plan">
        <form method="POST" action="{{ route('student.study-plan.add') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label class="label">Course</label>
                <select name="course_id" class="select" required>
                    <option value="">Select a course…</option>
                    @foreach ($replaceableCourses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }} ({{ $course->credits }} cr)</option>
                    @endforeach
                </select>
            </div>
            <label class="block">
                <span class="label">Year</span>
                <select name="year" class="select" required>
                    @foreach ($yearRange as $year)
                        <option value="{{ $year }}">Year {{ $year }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="label">Semester</span>
                <select name="semester" class="select" required>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3">Special Semester</option>
                </select>
            </label>

            <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Add course</button>
            </div>
        </form>
    </x-modal>
@endsection

@push('modals')
    <x-modal id="replace-elective-modal" title="Replace elective placeholder"
        subtitle="Choose the course that will fill <span data-replace-label class='font-mono font-semibold'></span>">
        <form method="POST" action="">
            @csrf
            @method('PUT')
            <label class="block">
                <span class="label">Replacement course</span>
                <select name="course_id" class="select" required>
                    <option value="">Select a course…</option>
                    @foreach ($replaceableCourses as $course)
                        <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }} ({{ $course->credits }} cr · {{ $course->courseType?->name }})</option>
                    @endforeach
                </select>
            </label>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="arrow-path" class="size-4" /> Apply replacement</button>
            </div>
        </form>
    </x-modal>
@endpush
