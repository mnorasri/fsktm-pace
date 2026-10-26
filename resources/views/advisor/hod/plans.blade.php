@extends('layouts.app')

@section('title', 'Study Plan')

@section('content')
    <x-page-header
        title="Study Plan"
        subtitle="Programme curricula, year by year — create a plan and place the courses that already exist in the course directory">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $plans->count() }} plan{{ $plans->count() === 1 ? '' : 's' }}</x-badge>
            @if ($plan)
                <button type="button" data-modal-open="import-plan-courses" class="btn-ghost">
                    <x-icon name="upload" class="size-4" /> Import CSV
                </button>
            @endif
            <button type="button" data-modal-open="add-plan" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New study plan
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    {{-- Choose the plan to look at. --}}
    <form method="GET" class="card card-pad mb-5 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-[28rem]">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Study plan</span>
            <select name="plan_id" data-auto-submit class="select">
                @forelse ($plans as $option)
                    <option value="{{ $option->id }}" @selected($plan?->id === $option->id)>
                        {{ $option->code }} · {{ $option->name }}
                        ({{ $option->plan_courses_count + $option->courses_count }} course{{ $option->plan_courses_count + $option->courses_count === 1 ? '' : 's' }})
                    </option>
                @empty
                    <option value="">No study plans yet</option>
                @endforelse
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-primary" @disabled($plans->isEmpty())>View</button>
        </div>
    </form>

    @if (! $plan)
        <x-card pad>
            <div class="py-12 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                    <x-icon name="calendar" class="size-7" />
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">No study plan has been entered yet</p>
                <p class="mt-1 text-sm text-slate-400">Create a plan, then add courses from the course directory term by term.</p>
                <button type="button" data-modal-open="add-plan" class="btn-primary mt-4">
                    <x-icon name="plus" class="size-4" /> New study plan
                </button>
            </div>
        </x-card>
    @else
        {{-- Plan summary --}}
        <x-card pad title="{{ $plan->code }}" subtitle="{{ $plan->name }} · {{ $plan->program?->code }} {{ $plan->program?->name }}">
            <x-slot:actions>
                <x-badge tone="slate">{{ $totals['courses'] }} course{{ $totals['courses'] === 1 ? '' : 's' }}</x-badge>
                <x-badge tone="emerald">{{ $totals['credits'] }} credits</x-badge>
                <x-badge tone="brand">{{ $totals['pinned'] }} placed in a term</x-badge>
                <button type="button" class="btn-primary btn-sm" data-edit="add-plan-course"
                    data-title="Add course to {{ $plan->code }}"
                    data-field-course_id=""
                    data-field-year="1"
                    data-field-semester="1"
                    data-field-category="required"
                    data-field-credits="">
                    <x-icon name="plus" class="size-3.5" /> Add course
                </button>
            </x-slot:actions>

            <dl class="mb-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 px-3.5 py-2.5">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Programme</dt>
                    <dd class="mt-0.5 font-semibold text-slate-700">{{ $plan->program?->code ?? '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3.5 py-2.5">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Department</dt>
                    <dd class="mt-0.5 font-semibold text-slate-700">{{ $plan->program?->department?->code ?? '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3.5 py-2.5">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Version</dt>
                    <dd class="mt-0.5 font-semibold text-slate-700">{{ $plan->version ?: '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 px-3.5 py-2.5">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Duration · students</dt>
                    <dd class="mt-0.5 font-semibold text-slate-700">
                        {{ $plan->program?->duration_years ? rtrim(rtrim(number_format((float) $plan->program->duration_years, 1), '0'), '.') : '—' }} years
                        · {{ $plan->students_count }}
                    </dd>
                </div>
            </dl>

            {{-- Year by year, odd semester beside the even one (and the special
                 semester in between, when the plan uses one). --}}
            @if ($totals['courses'] === 0)
                <div class="mb-5 flex items-start gap-3 rounded-2xl border border-dashed border-brand-200 bg-brand-50/40 px-4 py-3.5">
                    <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-600">
                        <x-icon name="info" class="size-4" />
                    </span>
                    <p class="text-sm text-slate-600">
                        This plan has no courses yet. Use
                        <span class="font-semibold text-slate-700">Add course</span>
                        above, or the Add button inside a term, to pick courses from the course directory.
                    </p>
                </div>
            @endif

            <div class="space-y-6">
                @foreach (range(1, $maxYear) as $year)
                    @php
                        $yearCredits = collect($years[$year] ?? [])->sum('credits');
                        $yearCourses = collect($years[$year] ?? [])->sum(fn ($term) => $term['rows']->count());
                    @endphp

                    <section class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="grid size-8 place-items-center rounded-xl bg-slate-900 text-[11px] font-bold text-white">Y{{ $year }}</span>
                            <h5 class="text-sm font-bold text-slate-700">Year {{ $year }}</h5>
                            <span class="text-[11px] font-semibold text-slate-400">
                                {{ $yearCourses }} course(s) · {{ $yearCredits }} cr
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-4 {{ count($semesters) === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                            @foreach ($semesters as $semester)
                                @php($term = $years[$year][$semester] ?? ['rows' => collect(), 'credits' => 0])

                                <div class="rounded-2xl border border-slate-200 p-3.5">
                                    <div class="mb-3 flex items-start justify-between gap-2 border-b border-slate-100 pb-2.5">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-700">{{ $semesterLabels[$semester] }}</p>
                                            <p class="text-xs text-slate-400">
                                                @if ($semester === 3)
                                                    Short semester
                                                @else
                                                    {{ $semester === 1 ? 'Odd' : 'Even' }} semester
                                                @endif
                                            </p>
                                        </div>
                                        <button type="button" class="btn-ghost btn-sm" data-edit="add-plan-course"
                                            data-title="Add a course to Year {{ $year }} {{ $semesterLabels[$semester] }}"
                                            data-field-course_id=""
                                            data-field-year="{{ $year }}"
                                            data-field-semester="{{ $semester }}"
                                            data-field-category="required"
                                            data-field-credits="">
                                            <x-icon name="plus" class="size-3.5" /> Add
                                        </button>
                                    </div>

                                    <div class="space-y-2">
                                        @forelse ($term['rows'] as $row)
                                            @include('advisor.hod.partials.plan-row', ['row' => $row, 'plan' => $plan])
                                        @empty
                                            <p class="rounded-2xl border border-dashed border-slate-200 px-3 py-6 text-center text-xs text-slate-400">
                                                No courses in this semester
                                            </p>
                                        @endforelse
                                    </div>

                                    <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                        <span class="font-semibold uppercase tracking-wide text-slate-400">Total credits</span>
                                        <span class="font-bold text-slate-700">{{ $term['credits'] }} cr</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </x-card>

        {{-- Courses attached to the plan but without a curriculum term (for
             example the elective catalogue, whose semester is 99). --}}
        @if ($unplaced->isNotEmpty())
            <x-card pad class="mt-5" title="Courses without a term"
                subtitle="Attached to this plan, but not placed in a year or semester yet">
                <x-slot:actions>
                    <x-badge tone="amber">{{ $unplaced->count() }}</x-badge>
                </x-slot:actions>

                <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($unplaced as $row)
                        <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $row['code'] }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $row['name'] }}</p>
                                <p class="mt-1 text-[11px] text-slate-400">{{ $row['credits'] }} cr · {{ $row['course']?->courseType?->name ?? 'Unset' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" class="btn-ghost btn-sm" data-edit="add-plan-course"
                                    data-title="Place {{ $row['code'] }} in the plan"
                                    data-field-course_id="{{ $row['course']?->id }}"
                                    data-field-year="1"
                                    data-field-semester="1"
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif
    @endif

    {{-- New plan --}}
    <x-modal id="add-plan" title="New study plan" size="lg" subtitle="Create the plan, then place its courses term by term">
        <form method="POST" action="{{ route('advisor.plans.store') }}">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="block sm:col-span-2">
                    <span class="label">Programme</span>
                    <select name="program_id" class="select" required>
                        <option value="">— Choose a programme —</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}">
                                {{ $program->code }} — {{ $program->name }} ({{ $program->department?->code ?? 'no department' }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="label">Plan code</span>
                    <input type="text" name="code" class="input" required maxlength="60" placeholder="BIM20252026-LOCAL">
                </label>
                <label class="block">
                    <span class="label">Version</span>
                    <input type="text" name="version" class="input" maxlength="40" placeholder="2025/2026">
                </label>

                <label class="block sm:col-span-2">
                    <span class="label">Plan name</span>
                    <input type="text" name="name" class="input" required maxlength="180" placeholder="BIM Local Students — Starting 2025/2026">
                </label>

                <label class="block">
                    <span class="label">Total credits</span>
                    <input type="number" name="total_credits" class="input" min="1" max="300" placeholder="120">
                </label>
                <label class="block">
                    <span class="label">Description</span>
                    <input type="text" name="description" class="input" maxlength="500" placeholder="Optional note">
                </label>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create plan</button>
            </div>
        </form>
    </x-modal>

    @if ($plan)
        {{-- Bulk import courses for this plan --}}
        <x-modal id="import-plan-courses" title="Import courses into {{ $plan->code }}"
            subtitle="CSV columns: course_code, year, semester — each row adds or updates the course's placement on this plan">
        <form method="POST" action="{{ route('advisor.plans.courses.import', $plan) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono">course_code,year,semester</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    <li><span class="font-semibold">course_code</span> must already exist in the course directory (matched case-insensitively).</li>
                    <li><span class="font-semibold">year</span> is the study year, 1–8.</li>
                    <li><span class="font-semibold">semester</span> is 1 (odd), 2 (even) or 3 (special).</li>
                </ul>
                <p class="mt-2">Existing rows are updated; new rows are added to this plan only. A row that fails is skipped and reported in the summary.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
        </x-modal>

        {{-- Add a course (also used to place a course that has no term yet) --}}
        <x-modal id="add-plan-course" title="Add course to {{ $plan->code }}" size="lg"
            subtitle="Pick a course from the course directory and give it a term">
            <form method="POST" action="{{ route('advisor.plans.courses.store', $plan) }}">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="label">Course</span>
                        <select name="course_id" class="select" required>
                            <option value="">— Choose a course —</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}">
                                    {{ $course->code }} — {{ $course->name }} ({{ $course->credits }} cr)
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="label">Year</span>
                        <select name="year" class="select" required>
                            @foreach (range(1, 8) as $year)
                                <option value="{{ $year }}">Year {{ $year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Semester</span>
                        <select name="semester" class="select" required>
                            @foreach ($semesterLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="label">Category</span>
                        <select name="category" class="select">
                            <option value="required">Required</option>
                            <option value="elective">Elective</option>
                            <option value="university">University</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Credits (blank = course credits)</span>
                        <input type="number" name="credits" class="input" min="0" max="20" placeholder="3">
                    </label>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                    <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Add to plan</button>
                </div>
            </form>
        </x-modal>

        {{-- Edit a placed row: move it to another term, change category/credits --}}
        <x-modal id="edit-plan-row" title="Edit study plan row" subtitle="{{ $plan->code }}">
            <form method="POST" action="">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="label">Year</span>
                        <select name="year" class="select" required>
                            @foreach (range(1, 8) as $year)
                                <option value="{{ $year }}">Year {{ $year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Semester</span>
                        <select name="semester" class="select" required>
                            @foreach ($semesterLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Category</span>
                        <select name="category" class="select" required>
                            <option value="required">Required</option>
                            <option value="elective">Elective</option>
                            <option value="university">University</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Credits</span>
                        <input type="number" name="credits" class="input" min="0" max="20">
                    </label>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                    <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
