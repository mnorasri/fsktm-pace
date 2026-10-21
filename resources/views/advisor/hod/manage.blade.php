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
                                <th>Registered by PAK group</th>
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

                                    {{-- The same students, totalled per PAK group. --}}
                                    <td class="align-top!">
                                        @if ($registeredRows->isEmpty())
                                            <p class="text-[11px] text-slate-400">No registrations yet</p>
                                        @else
                                            <ul class="space-y-1">
                                                @foreach ($registeredRows as $row)
                                                    <li class="flex items-center gap-2">
                                                        <x-badge tone="brand">{{ $row['students'] }}</x-badge>
                                                        <span class="max-w-[9rem] truncate text-[11px] font-medium text-slate-600"
                                                            title="{{ $row['advisor']?->name ?? 'No advisor assigned' }}">
                                                            {{ $row['advisor']?->name ?? 'No advisor assigned' }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>

                                    {{-- One save per PAK group: section 01–10 + its lecturer.
                                         The first row is the course as a whole, for the
                                         courses no PAK group has registered for. --}}
                                    <td class="align-top!">
                                        <div class="w-[27rem] space-y-2">
                                            <form method="POST" action="{{ route('advisor.offering.assign', $offering) }}" data-scroll-keep
                                                class="grid grid-cols-[minmax(0,1fr)_5.5rem_minmax(0,1.4fr)_auto] items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-2">
                                                @csrf
                                                @method('PUT')
                                                <span class="truncate text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                                                    title="The course as a whole, before a PAK group has its own section">
                                                    Whole course
                                                </span>
                                                <select name="section" class="select py-1.5 text-xs" aria-label="Section for the whole course">
                                                    <option value="" title="No section">None</option>
                                                    @foreach ($sectionChoices($offering->section) as $section)
                                                        <option value="{{ $section }}" @selected($offering->section === $section)>{{ $section }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="lecturer_id" class="select py-1.5 text-xs" aria-label="Lecturer for the whole course">
                                                    <option value="">— Lecturer —</option>
                                                    @foreach ($advisors as $advisor)
                                                        <option value="{{ $advisor->id }}" @selected($offering->lecturer_id === $advisor->id)>{{ $advisor->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn-subtle btn-sm">
                                                    <x-icon name="check" class="size-3.5" /> Save
                                                </button>
                                            </form>

                                            @foreach ($panel as $row)
                                                @if ($row['advisor'])
                                                    <form method="POST" action="{{ route('advisor.offering.group', $offering) }}" data-scroll-keep
                                                        class="grid grid-cols-[minmax(0,1fr)_5.5rem_minmax(0,1.4fr)_auto] items-center gap-2 rounded-xl border border-slate-200 bg-white p-2">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="advisor_id" value="{{ $row['advisor']->id }}">
                                                        <span class="min-w-0 text-[11px] font-semibold text-slate-600"
                                                            title="{{ $row['advisor']->name }} · PAK group">
                                                            <span class="block truncate">{{ $row['advisor']->name }}</span>
                                                            <span class="block text-[10px] font-normal text-slate-400">{{ $row['students'] }} student(s)</span>
                                                        </span>
                                                        <select name="section" class="select py-1.5 text-xs"
                                                            aria-label="Section for {{ $row['advisor']->name }}">
                                                            <option value="" title="No section">None</option>
                                                            @foreach ($sectionChoices($row['assignment']?->section) as $section)
                                                                <option value="{{ $section }}" @selected($row['assignment']?->section === $section)>{{ $section }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="lecturer_id" class="select py-1.5 text-xs"
                                                            aria-label="Lecturer for {{ $row['advisor']->name }}">
                                                            <option value="">— Lecturer —</option>
                                                            @foreach ($advisors as $advisor)
                                                                <option value="{{ $advisor->id }}" @selected($row['assignment']?->lecturer_id === $advisor->id)>{{ $advisor->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="btn-subtle btn-sm">
                                                            <x-icon name="check" class="size-3.5" /> Save
                                                        </button>
                                                    </form>
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
                                        <form method="POST" action="{{ route('advisor.offering.assign', $offering) }}" data-scroll-keep
                                            class="min-w-[12rem] space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="coordinator_id" class="select py-1.5 text-xs"
                                                @disabled($coordinatorOptions->isEmpty()) aria-label="Coordinator for {{ $offering->course->code }}">
                                                <option value="">— Coordinator —</option>
                                                @foreach ($coordinatorOptions as $advisor)
                                                    <option value="{{ $advisor->id }}" @selected($offering->coordinator_id === $advisor->id)>{{ $advisor->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn-subtle btn-sm w-full justify-center" @disabled($coordinatorOptions->isEmpty())>
                                                <x-icon name="check" class="size-3.5" /> Save coordinator
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
                                    </td>
                                </tr>

                                {{-- Everything that is not part of the assignment itself:
                                     kept out of the way, collapsed. --}}
                                <tr class="no-print">
                                    <td colspan="6" class="bg-slate-50/40 p-0">
                                        <details class="group px-4 py-1.5">
                                            <summary class="flex cursor-pointer list-none items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-slate-400 transition hover:text-slate-600 [&::-webkit-details-marker]:hidden">
                                                <x-icon name="chevron-down" class="size-3.5 -rotate-90 transition group-open:rotate-0" />
                                                Course details · capacity, schedule, primary PAK group
                                            </summary>
                                            <form method="POST" action="{{ route('advisor.offering.assign', $offering) }}" data-scroll-keep
                                                class="mt-2 mb-2 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-3">
                                                @csrf
                                                @method('PUT')
                                                <label class="block">
                                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Capacity</span>
                                                    <input type="number" name="capacity" value="{{ $offering->capacity }}" min="1" max="500" class="input w-24 py-1.5 text-xs">
                                                </label>
                                                <label class="block">
                                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Day</span>
                                                    <input type="text" name="day" value="{{ $offering->day }}" placeholder="Monday" class="input w-32 py-1.5 text-xs">
                                                </label>
                                                <label class="block">
                                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Time</span>
                                                    <input type="text" name="time" value="{{ $offering->time }}" placeholder="8:00 – 10:00" class="input w-36 py-1.5 text-xs">
                                                </label>
                                                <label class="block">
                                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Venue</span>
                                                    <input type="text" name="venue" value="{{ $offering->venue }}" placeholder="BK 1" class="input w-32 py-1.5 text-xs">
                                                </label>
                                                <label class="block">
                                                    <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Primary PAK group</span>
                                                    <select name="advisee_group_id" class="select w-56 py-1.5 text-xs">
                                                        <option value="">— PAK Group —</option>
                                                        @foreach ($advisors as $advisor)
                                                            <option value="{{ $advisor->id }}" @selected($offering->advisee_group_id === $advisor->id)>{{ $advisor->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <button class="btn-subtle btn-sm">
                                                    <x-icon name="check" class="size-3.5" /> Save details
                                                </button>
                                            </form>
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
