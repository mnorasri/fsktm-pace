@extends('layouts.app')

@section('title', 'Manage Offering')

@section('content')
    <x-page-header title="Manage Offering"
        subtitle="Registered students per course, split by PAK group · give each PAK group its section and lecturer, then a coordinator for the course">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
            <a href="{{ route('advisor.offering.export') }}" class="btn-ghost no-print">
                <x-icon name="download" class="size-4" /> Export CSV
            </a>
            <button type="button" onclick="window.print()" class="btn-primary no-print">
                <x-icon name="printer" class="size-4" /> Print
            </button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card card-pad no-print mb-5 flex flex-wrap items-end gap-3">
        <div class="min-w-[16rem] flex-1">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Search</span>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search course code or title…"
                    class="input pl-9">
            </div>
        </div>
        <div class="w-full sm:w-[14rem]">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Course group</span>
            <select name="group" data-auto-submit class="select">
                <option value="">All course groups</option>
                @foreach ($groupOptions as $option)
                    <option value="{{ $option }}" @selected($courseGroup === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-primary">Filter</button>
            @if (request('search') || $courseGroup)
                <a href="{{ route('advisor.offering.manage') }}" class="btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    @php
        $totalOfferings = $offerings->count();
        $totalRegistered = $offerings->sum('pre_registrations_count');

        // The section already saved may sit outside 01–10 (older data): keep it
        // selectable so a save never loses it.
        $sectionChoices = fn (?string $current) => $current && ! $sectionOptions->contains($current)
            ? $sectionOptions->merge([$current])->sort()->values()
            : $sectionOptions;
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Offered Courses" :value="$totalOfferings" icon="book" tone="brand" />
        <x-stat-card label="Total Registrations" :value="$totalRegistered" icon="users" tone="emerald" />
        <x-stat-card label="Course Prefix Groups" :value="$groups->count()" icon="layers" tone="sky" />
    </div>

    {{-- Every PAK group (academic advisor) and the students they registered
         for the active term, across all the courses offered. --}}
    @if ($totalsByAdvisor->isNotEmpty())
        <x-card class="mt-4" title="Registrations by PAK group"
            subtitle="Students already registered for {{ $activeSession }} · Semester {{ $activeSemester }}, grouped by their academic advisor">
            <x-slot:actions>
                <x-badge tone="emerald">{{ $totalsByAdvisor->sum('students') }} registration(s)</x-badge>
            </x-slot:actions>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($totalsByAdvisor as $row)
                    <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-3.5 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-slate-700">
                                {{ $row['advisor']?->name ?? 'No advisor assigned' }}
                            </p>
                            <p class="text-[11px] text-slate-400">
                                {{ $row['advisor'] ? 'PAK group' : 'students without an advisor' }}
                            </p>
                        </div>
                        <x-badge tone="brand">{{ $row['students'] }}</x-badge>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif

    <div class="mt-6 space-y-5">
        @forelse ($groups as $prefix => $items)
            <x-card pad title="{{ $prefix }} · Course Group"
                subtitle="{{ $items->count() }} offering(s) · {{ $items->sum('pre_registrations_count') }} registration(s)">
                <x-slot:actions>
                    <x-badge tone="slate">{{ $prefix }}</x-badge>
                </x-slot:actions>

                <div class="-mx-6 overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th class="text-center">Registered</th>
                                <th>Section &amp; lecturer per PAK group</th>
                                <th>Coordinator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $offering)
                                @php
                                    $panel = $groupPanels[$offering->id] ?? collect();
                                    $registeredRows = $panel->filter(fn ($row) => $row['students'] > 0)->values();

                                    // A coordinator may only be one of the lecturers
                                    // already attached to this course.
                                    $lecturers = $assignedLecturers[$offering->id] ?? collect();
                                    $coordinatorOptions = $offering->coordinator
                                        ? $lecturers->push($offering->coordinator)->unique('id')->sortBy('name')->values()
                                        : $lecturers;
                                @endphp
                                <tr>
                                    <td class="align-top!">
                                        <p class="whitespace-nowrap font-mono text-xs font-semibold text-slate-800">{{ $offering->course->code }}</p>
                                        @if ($offering->section)
                                            <p class="mt-0.5 text-[11px] text-slate-400">Section {{ $offering->section }}</p>
                                        @endif
                                    </td>

                                    <td class="align-top!">
                                        <p class="max-w-[11rem] text-xs font-medium text-slate-700">{{ $offering->course->name }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            {{ (int) $offering->course->credits }} cr
                                        </p>
                                    </td>

                                    {{-- Total students registered for the course. --}}
                                    <td class="align-top! text-center">
                                        <p class="text-lg font-bold leading-tight text-slate-800">{{ $offering->pre_registrations_count }}</p>
                                        <p class="text-[11px] text-slate-400">student(s)</p>
                                        @if ($offering->approved_count > 0)
                                            <p class="text-[10px] font-semibold text-emerald-600">{{ $offering->approved_count }} approved</p>
                                        @endif
                                    </td>

                                    {{-- Sections are managed in Step 1 above; the PAK group
                                         registration breakdown is no longer shown here. --}}

                                    {{-- One save per PAK group: section 01–10 + its lecturer.
                                         The first row is the course as a whole, for the
                                         courses no PAK group has registered for. --}}
                                    <td class="align-top!">
                                        <div class="w-[27rem] space-y-2">
                                            <details class="rounded-xl border border-brand-200 bg-brand-50/40 p-2" open>
                                                <summary class="cursor-pointer list-none text-[11px] font-semibold text-brand-700">
                                                    Step 1 - Create sections
                                                </summary>
                                                <div class="mt-2 space-y-2">
                                                    <form method="POST" action="{{ route('advisor.offering.section.store', $offering) }}" data-scroll-keep
                                                        class="flex flex-wrap items-center gap-2">
                                                        @csrf
                                                        <select name="section" class="select py-1.5 text-xs" aria-label="New section number">
                                                            <option value="">None</option>
                                                            @foreach ($sectionOptions as $section)
                                                                <option value="{{ $section }}">{{ $section }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="lecturer_id" class="select py-1.5 text-xs" aria-label="Lecturer for the new section">
                                                            <option value="">Lecturer</option>
                                                            @foreach ($advisors as $advisor)
                                                                <option value="{{ $advisor->id }}">{{ $advisor->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="btn-success btn-sm">Save</button>
                                                    </form>
                                                    @php($sectionsForThis = $offeringSections[$offering->id] ?? collect())
                                                    @if ($sectionsForThis->isNotEmpty())
                                                        <div class="space-y-1">
                                                            @foreach ($sectionsForThis as $os)
                                                                <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[11px]">
                                                                    <span class="font-semibold text-slate-700">Section {{ $os->section }}</span>
                                                                    <span class="truncate text-slate-400">{{ $os->lecturer?->name ?? 'Lecturer' }}</span>
                                                                    <form method="POST" action="{{ route('advisor.offering.section.delete', $os) }}" data-scroll-keep
                                                                        onsubmit="return confirm('Remove section {{ $os->section }}?');">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button class="btn-ghost btn-sm">
                                                                            <x-icon name="x" class="size-3.5" />
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-[10px] text-slate-400">No sections yet.</p>
                                                    @endif

                                                    @php($hasSectionSetup = $sectionsForThis->isNotEmpty()
                                                        || $offering->groups->contains(fn ($group) => filled($group->section))
                                                        || $offering->coordinator_id)
                                                    @if ($hasSectionSetup)
                                                        <form method="POST" action="{{ route('advisor.offering.sections.reset', $offering) }}" data-scroll-keep
                                                            onsubmit="return confirm('Reset all section settings for {{ $offering->course->code }}? This clears the sections, assigned lecturers, student section assignments and the coordinator.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn-danger btn-sm w-full justify-center">
                                                                <x-icon name="arrow-path" class="size-3.5" /> Reset sections
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </details>

                                            @foreach ($panel as $row)
                                                @if ($row['advisor'])
                                                    <div class="space-y-2 rounded-xl border border-slate-200 bg-white p-2">
                                                        <span class="block text-[11px] font-semibold text-slate-600">{{ $row['advisor']->name }}</span>
                                                        <span class="block text-[10px] text-slate-400">{{ $row['students'] }} student(s) in this PAK group</span>

                                                        @php($offeringSectionsForThis = $offeringSections[$offering->id] ?? collect())
                                                        @if (($row['student_list'] ?? collect())->isNotEmpty() && $offeringSectionsForThis->isNotEmpty())
                                                            <form method="POST" action="{{ route('advisor.offering.distribute-sections', $offering) }}" data-scroll-keep
                                                                class="space-y-2">
                                                                @csrf
                                                                <input type="hidden" name="advisor_id" value="{{ $row['advisor']->id }}">
                                                                <p class="text-[11px] font-semibold text-slate-600">Step 2 · Distribute students to sections</p>
                                                                <div class="space-y-1">
                                                                    @foreach ($offeringSectionsForThis as $os)
                                                                        <div class="flex items-center gap-2">
                                                                            <span class="w-20 text-[11px] font-semibold text-slate-600">Section {{ $os->section }}</span>
                                                                            <input type="hidden" name="sections[]" value="{{ $os->section }}">
                                                                            <input type="number" name="counts[]" value="0" min="0"
                                                                                class="input w-20 py-1 text-xs">
                                                                            <span class="text-[10px] text-slate-400">student(s)</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                                <button class="btn-success btn-sm">
                                                                    <x-icon name="arrow-path" class="size-3.5" /> Distribute randomly
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if (($row['student_list'] ?? collect())->isNotEmpty())
                                                            <details class="rounded-xl border border-slate-100 bg-slate-50/60">
                                                                <summary class="cursor-pointer list-none px-2 py-1.5 text-[11px] font-semibold text-slate-600">
                                                                    <x-icon name="users" class="size-3.5" /> Step 3 · View {{ $row['students'] }} registered student(s) by section
                                                                </summary>
                                                                @php($bySection = collect($row['student_list'])->groupBy(fn ($st) => $st['section'] ?: 'Unassigned'))
                                                                @foreach ($bySection as $section => $students)
                                                                    <div class="px-2 pb-2">
                                                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Section {{ $section }}</p>
                                                                        <ul class="space-y-0.5 text-[11px] text-slate-600">
                                                                            @foreach ($students as $st)
                                                                                <li class="flex items-center justify-between gap-2">
                                                                                    <span class="truncate">{{ $st['name'] }}</span>
                                                                                    <span class="font-mono text-[10px] text-slate-400">{{ $st['matric'] }}</span>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                @endforeach
                                                            </details>
                                                        @endif
                                                    </div>
                                                @else
                                                    <p class="rounded-xl border border-dashed border-amber-200 bg-amber-50/60 px-3 py-2 text-[11px] text-amber-700">
                                                        {{ $row['students'] }} student(s) registered without a PAK group —
                                                        give each student an academic advisor to split them into their own section.
                                                    </p>
                                                @endif
                                            @endforeach

                                            @if ($panel->isEmpty())
                                                <p class="rounded-xl border border-dashed border-slate-200 px-3 py-2 text-[11px] text-slate-400">
                                                    No registrations yet — a PAK group row appears here as its students register.
                                                </p>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- One coordinator for the course, chosen from the
                                         lecturers already given a section. --}}
                                    <td class="align-top!">
                                        <details class="min-w-[12rem] rounded-xl border border-slate-200 bg-white p-2" {{ $offering->coordinator_id ? '' : 'open' }}>
                                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2">
                                                <span class="text-[11px] text-slate-600">
                                                    @if ($offering->coordinator)
                                                        {{ $offering->coordinator->name }}
                                                    @else
                                                        — Coordinator —
                                                    @endif
                                                </span>
                                                <span class="btn-blue btn-sm shrink-0">
                                                    <x-icon name="pencil" class="size-3.5" /> Edit
                                                </span>
                                            </summary>
                                            <div class="mt-2 border-t border-slate-100 pt-2">
                                                <form method="POST" action="{{ route('advisor.offering.assign', $offering) }}" data-scroll-keep
                                                    class="space-y-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="coordinator_id" class="select py-1.5 text-xs"
                                                        @disabled($coordinatorOptions->isEmpty()) aria-label="Coordinator for {{ $offering->course->code }}">
                                                        <option value="">— Coordinator —</option>
                                                        @foreach ($coordinatorOptions as $advisor)
                                                            <option value="{{ $advisor->id }}" @selected($offering->coordinator_id === $advisor->id)>{{ $advisor->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn-success btn-sm w-full justify-center" @disabled($coordinatorOptions->isEmpty())>
                                                        <x-icon name="check" class="size-3.5" /> Save
                                                    </button>
                                                    <p class="text-[11px] leading-snug text-slate-400">
                                                        @if ($coordinatorOptions->isEmpty())
                                                            Assign a lecturer to a section first.
                                                        @elseif ($offering->coordinator)
                                                            <span class="font-semibold text-slate-600">{{ $offering->coordinator->name }}</span> coordinates this course.
                                                        @else
                                                            {{ $coordinatorOptions->count() }} assigned lecturer(s) to choose from.
                                                        @endif
                                                    </p>
                                                </form>
                                                @if ($offering->coordinator)
                                                    <form method="POST" action="{{ route('advisor.offering.coordinator.revoke', $offering) }}" data-scroll-keep
                                                        class="mt-2"
                                                        onsubmit="return confirm('Revoke coordinator for {{ $offering->course->code }}?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn-danger btn-sm w-full justify-center">
                                                            <x-icon name="x" class="size-3.5" /> Revoke coordinator
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-empty-state title="No offerings for this term" icon="clipboard"
                :message="$courseGroup || request('search')
                    ? 'No offered course matches this filter.'
                    : 'Use the Course Offering grid to publish courses for the active session and semester.'" />
        @endforelse
    </div>
@endsection
