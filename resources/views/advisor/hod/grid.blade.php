@extends('layouts.app')

@section('title', 'Course Offering')

@section('content')
    <x-page-header title="Course Offering Toggle Grid"
        subtitle="Courses from each programme plan for {{ $activeSession }} · Semester {{ $activeSemester }}, grouped by year and semester">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
            <button type="button" onclick="window.print()" class="btn-primary no-print">
                <x-icon name="printer" class="size-4" /> Print
            </button>
        </x-slot:actions>
    </x-page-header>

    <p class="no-print mb-5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500">
        Each plan is listed <span class="font-semibold text-slate-600">year by year</span>, following the
        study plan's own year and semester (from Study Plan Details).
        <span class="font-semibold text-slate-600">Elective courses</span> belong to no single year — they sit in
        their own panel at the bottom of the plan. Toggle a course to add or remove it from the active term's
        offerings — published courses appear highlighted.
    </p>

    <form method="GET" class="card card-pad no-print mb-5 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-[26rem]">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Course plan</span>
            <select name="plan_id" data-auto-submit class="select">
                <option value="">All course plans</option>
                @foreach ($planOptions as $plan)
                    <option value="{{ $plan->id }}" @selected((int) request('plan_id') === $plan->id)>
                        {{ $plan->code }} · {{ $plan->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-primary">Filter</button>
            @if ($selectedPlan)
                <a href="{{ route('advisor.offering.grid') }}" class="btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    @php
        $withCourses = $plans->filter(fn ($plan) => $plan->courses->isNotEmpty());
        $withoutCourses = $plans->filter(fn ($plan) => $plan->courses->isEmpty());
    @endphp

    <div class="space-y-6">
        @forelse ($withCourses as $plan)
            @php
                // Placement (year + semester) comes from the study plan (plan_courses),
                // not from the course's own curriculum semester.
                $placed = $plan->courses->filter(fn ($course) => $course->plan_year !== null && $course->plan_semester !== null)->values();
                $unplaced = $plan->courses->reject(fn ($course) => $course->plan_year !== null && $course->plan_semester !== null)->values();
                $electives = $unplaced->filter(fn ($course) => (int) $course->semester === 0 || (int) $course->semester >= 11)->values();
                $withoutTerm = $unplaced->reject(fn ($course) => (int) $course->semester === 0 || (int) $course->semester >= 11)->values();
                $maxYear = (int) ($placed->max('plan_year') ?? 0);
                if ($maxYear < 1) { $maxYear = 1; }
                $hasSpecial = $placed->contains(fn ($course) => (int) $course->plan_semester === 3);
                $semesters = $hasSpecial ? [1, 2, 3] : [1, 2];
                $offeredCount = $plan->courses->filter(fn ($course) => isset($existing[$course->id]))->count();
            @endphp

            <x-card pad title="{{ $plan->code }}" subtitle="{{ $plan->name }} · {{ $plan->program?->name }}">
                <x-slot:actions>
                    <x-badge tone="slate">{{ $plan->courses->count() }} courses</x-badge>
                    @if ($electives->isNotEmpty())
                        <x-badge tone="brand">{{ $electives->count() }} elective</x-badge>
                    @endif
                    <x-badge :tone="$offeredCount > 0 ? 'emerald' : 'slate'">{{ $offeredCount }} offered</x-badge>
                </x-slot:actions>

                <div class="space-y-5">
                    @foreach ($maxYear > 0 ? range(1, $maxYear) : [] as $year)
                        @php
                            $yearCourses = $placed->filter(fn ($course) => (int) $course->plan_year === $year)->values();
                        @endphp

                        @if ($yearCourses->isNotEmpty())
                            <section class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="grid size-8 place-items-center rounded-xl bg-slate-900 text-[11px] font-bold text-white">Y{{ $year }}</span>
                                    <h5 class="text-sm font-bold text-slate-700">Year {{ $year }}</h5>
                                    <span class="text-[11px] font-semibold text-slate-400">
                                        {{ $yearCourses->count() }} course(s) · {{ $yearCourses->sum('credits') }} cr
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 gap-4 {{ count($semesters) === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                                    @foreach ($semesters as $semester)
                                        @php
                                            $semesterCourses = $placed->filter(fn ($course) => (int) $course->plan_year === $year && (int) $course->plan_semester === $semester)->values();
                                        @endphp
                                        <div class="rounded-2xl border border-slate-200 p-3.5">
                                            <div class="mb-3 flex items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-700">Semester {{ $semester }}</p>
                                                    <p class="text-xs text-slate-400">
                                                        @if ($semester === 3)
                                                            Short semester
                                                        @elseif ($semester === 1)
                                                            Odd semester
                                                        @else
                                                            Even semester
                                                        @endif
                                                    </p>
                                                </div>
                                                <x-badge :tone="$semester === 1 ? 'brand' : ($semester === 3 ? 'amber' : 'sky')">
                                                    {{ $semesterCourses->count() }}
                                                </x-badge>
                                            </div>

                                            <div class="space-y-2.5">
                                                @forelse ($semesterCourses as $course)
                                                    <x-offering-toggle :course="$course" :plan="$plan"
                                                        :offered="isset($existing[$course->id])"
                                                        :year="$year" />
                                                @empty
                                                    <p class="rounded-2xl border border-dashed border-slate-200 px-3 py-6 text-center text-xs text-slate-400">
                                                        No courses in this semester
                                                    </p>
                                                @endforelse
                                            </div>

                                            <div class="mt-3 flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                                <span class="font-semibold uppercase tracking-wide text-slate-400">Total credits</span>
                                                <span class="font-bold text-slate-700">{{ $semesterCourses->sum('credits') }} cr</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach

                    @if ($withoutTerm->isNotEmpty())
                        <div class="mt-6 scroll-mt-24 rounded-2xl border border-slate-200 bg-slate-50/40 p-3.5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-8 place-items-center rounded-xl bg-slate-100 text-slate-600">
                                        <x-icon name="calendar" class="size-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">Courses without a term</p>
                                        <p class="text-xs text-slate-400">Attached to this plan, but not placed in a year or semester yet</p>
                                    </div>
                                </div>
                                <x-badge tone="slate">{{ $withoutTerm->count() }}</x-badge>
                            </div>

                            <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($withoutTerm as $course)
                                    <x-offering-toggle :course="$course" :plan="$plan"
                                        :offered="isset($existing[$course->id])" />
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Elective courses have no year of their own: the student fills the
                         plan's Elective Option slots with them, in whichever term. --}}
                    @if ($electives->isNotEmpty())
                        <div id="elective-courses-{{ $plan->id }}" class="mt-6 scroll-mt-24 rounded-2xl border border-brand-200 bg-brand-50/40 p-3.5">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-brand-100 pb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-8 place-items-center rounded-xl bg-brand-100 text-brand-600">
                                        <x-icon name="sparkles" class="size-4" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">Elective Course</p>
                                        <p class="text-xs text-slate-400">
                                            Offered without a year — chosen by the student for an Elective Option slot
                                        </p>
                                    </div>
                                </div>
                                <x-badge tone="brand">{{ $electives->count() }}</x-badge>
                            </div>

                            <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($electives as $course)
                                    <x-offering-toggle :course="$course" :plan="$plan"
                                        :offered="isset($existing[$course->id])" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-card>
            @empty
                <x-card pad>
                    <p class="py-10 text-center text-sm text-slate-400">
                        @if ($selectedPlan)
                            This programme plan has no courses attached to it yet.
                        @else
                            No courses are attached to any programme plan yet.
                        @endif
                    </p>
                </x-card>
            @endforelse
        </div>

        @if ($withoutCourses->isNotEmpty())
            <p class="mt-5 text-xs text-slate-400">
                Plans without courses:
                {{ $withoutCourses->pluck('code')->implode(', ') }}
            </p>
        @endif
    @endsection