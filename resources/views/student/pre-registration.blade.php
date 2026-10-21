@extends('layouts.app')

@section('title', 'Pre-Registration')

@section('content')
    <x-page-header title="Course Pre-Registration"
        subtitle="{{ $activeSession }} · Semester {{ $activeSemester }} — {{ $planApproved ? 'your study plan, with only the courses offered this term enabled' : 'preview only — registration opens once your study plan is approved' }}">
        <x-slot:actions>
            <x-badge tone="{{ $committedCredits >= $cap ? 'rose' : 'brand' }}" dot>
                {{ $committedCredits }} / {{ $cap }} credits
            </x-badge>
            <a href="{{ route('student.slip') }}" class="btn-primary">
                <x-icon name="printer" class="size-4" /> Generate slip
            </a>
        </x-slot:actions>
    </x-page-header>

    @unless ($planApproved)
        <div class="mb-5 flex flex-wrap items-start gap-3 rounded-3xl border border-amber-200 bg-amber-50 p-5">
            <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600">
                <x-icon name="lock" class="size-5" />
            </span>
            <div class="min-w-[16rem] flex-1">
                <p class="text-sm font-bold text-amber-900">
                    Registration is locked until your advisor approves your study plan
                </p>
                <p class="mt-1 text-sm text-amber-800">
                    Your plan is
                    <span class="font-semibold">
                        {{ $student->plan_status === 'submitted' ? "waiting for your advisor's review" : $student->plan_status }}
                    </span>
                    @if ($student->plan_review_note)
                        — &ldquo;{{ $student->plan_review_note }}&rdquo;
                    @endif
                    . You can browse the courses offered this term, but only an approved plan may be registered.
                </p>
            </div>
            <a href="{{ route('student.study-plan') }}" class="btn-ghost btn-sm">
                <x-icon name="book" class="size-3.5" /> Open my study plan
            </a>
        </div>
    @endunless

    {{-- The term's pre-registration is sent to the advisor as one batch. --}}
    @if ($planApproved)
        @php
            $requestedCredits = (int) $registrations->sum('credits');
        @endphp

        @if ($submission?->status === 'submitted')
            <div class="mb-5 flex flex-wrap items-start gap-3 rounded-3xl border border-amber-200 bg-amber-50 p-5">
                <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600">
                    <x-icon name="arrow-path" class="size-5" />
                </span>
                <div class="min-w-[16rem] flex-1">
                    <p class="text-sm font-bold text-amber-900">
                        Pre-registration submitted — awaiting your advisor&rsquo;s approval
                    </p>
                    <p class="mt-1 text-sm text-amber-800">
                        Sent {{ $submission->submitted_at?->format('d M Y, H:i') }} ·
                        {{ $registrationCount }} course(s) · {{ $requestedCredits }} credits.
                        Your selection is locked until {{ $student->advisor?->name ?? 'your advisor' }} responds.
                    </p>
                </div>
                <x-badge tone="amber" dot>Awaiting approval</x-badge>
            </div>
        @elseif ($submission?->status === 'approved')
            <div class="mb-5 flex flex-wrap items-start gap-3 rounded-3xl border border-emerald-200 bg-emerald-50 p-5">
                <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-600">
                    <x-icon name="check" class="size-5" />
                </span>
                <div class="min-w-[16rem] flex-1">
                    <p class="text-sm font-bold text-emerald-900">
                        Pre-registration approved by {{ $submission->reviewer?->name ?? 'your advisor' }}
                    </p>
                    <p class="mt-1 text-sm text-emerald-800">
                        Approved {{ $submission->reviewed_at?->format('d M Y, H:i') }} ·
                        {{ $registrationCount }} course(s) · {{ $requestedCredits }} credits.
                        This is your confirmed registration for {{ $activeSession }} · Semester {{ $activeSemester }}.
                    </p>
                </div>
                <x-badge tone="emerald" dot>Approved</x-badge>
            </div>
        @else
            <div class="mb-5 flex flex-wrap items-start gap-3 rounded-3xl border p-5
                {{ $submission?->status === 'returned' ? 'border-rose-200 bg-rose-50' : 'border-brand-200 bg-brand-50' }}">
                <span class="grid size-9 shrink-0 place-items-center rounded-2xl
                    {{ $submission?->status === 'returned' ? 'bg-rose-100 text-rose-600' : 'bg-brand-100 text-brand-600' }}">
                    <x-icon :name="$submission?->status === 'returned' ? 'warning' : 'clipboard'" class="size-5" />
                </span>
                <div class="min-w-[16rem] flex-1">
                    @if ($submission?->status === 'returned')
                        <p class="text-sm font-bold text-rose-900">Returned by your advisor — please revise and resubmit</p>
                        <p class="mt-1 text-sm text-rose-800">
                            &ldquo;{{ $submission->note }}&rdquo;
                            @if ($submission->reviewed_at) · {{ $submission->reviewed_at->format('d M Y, H:i') }} @endif
                        </p>
                    @else
                        <p class="text-sm font-bold text-brand-900">Send your pre-registration to your advisor for approval</p>
                        <p class="mt-1 text-sm text-brand-800">
                            {{ $registrationCount > 0
                                ? $registrationCount.' course(s) · '.$requestedCredits.' credits ready to submit.'
                                : 'Add at least one course below, then submit it for approval.' }}
                        </p>
                    @endif
                </div>
                <form method="POST" action="{{ route('student.pre-registration.submit') }}" data-scroll-keep>
                    @csrf
                    <button class="btn-primary" @disabled($registrationCount === 0)>
                        <x-icon name="check" class="size-4" /> Submit for approval
                    </button>
                </form>
            </div>
        @endif
    @endif

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Committed" :value="$committedCredits" icon="check" tone="brand" hint="Pending + approved" />
        <x-stat-card label="Remaining" :value="$remainingCredits" icon="chart" tone="emerald" hint="Before the {{ $cap }}-credit cap" />
        <x-stat-card label="Credit Cap" :value="$cap" icon="lock" tone="rose" hint="Hard limit per term" />
        <x-stat-card label="Offered Courses" :value="$offeredCount" icon="book" tone="sky" hint="{{ $offeredInPlan }} from your plan" />
    </div>

    {{-- Legend --}}
    <div class="mt-5 flex flex-wrap items-center gap-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-brand-500"></span> Offered — can be added</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Selected</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-rose-500"></span> Locked — prerequisite missing</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-400"></span> Awaiting approval — plan or advisor</span>
        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-slate-300"></span> Not offered this term</span>
    </div>

    <div class="mt-4">
        <input type="search" data-filter-input="#offering-list" placeholder="Search your study plan…" class="input">
    </div>

    {{-- Study plan grid — one row per year: odd semester, (special semester), even semester --}}
    @php
        $hasSpecial = \in_array(3, $semesters, true);
    @endphp

    <div id="offering-list" class="mt-5 space-y-7">
        @foreach ($years as $year => $terms)
            <section class="space-y-4">
                <div class="flex items-center gap-3 pt-2">
                    <span class="grid size-9 place-items-center rounded-2xl bg-slate-900 text-xs font-bold text-white">Y{{ $year }}</span>
                    <h2 class="text-lg font-bold text-slate-800">Year {{ $year }}</h2>
                </div>

                <div class="grid grid-cols-1 gap-5 {{ $hasSpecial ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                    @foreach ($semesters as $semester)
                        @php
                            $term = $terms[$semester];
                            $offeredHere = $term['entries']->filter(fn ($entry) => ($rows[$entry->id]['offering'] ?? null) !== null)->count();
                        @endphp
                        <x-card pad
                            title="{{ $semesterLabels[$semester] }}"
                            subtitle="{{ $term['entries']->count() }} course(s) · {{ $offeredHere }} offered · {{ $term['credits'] }} credits">
                            @if ($term['entries']->isEmpty())
                                <x-empty-state title="No courses in this term" icon="calendar"
                                    message="Nothing planned for Year {{ $year }} {{ $semesterLabels[$semester] }}." />
                            @else
                                <ul class="space-y-2.5">
                                    @foreach ($term['entries'] as $entry)
                                        @php
                                            $row = $rows[$entry->id];
                                            $course = $row['course'];
                                            $offering = $row['offering'];

                                            $icon = $row['selected'] ? 'check' : ($course === null ? 'sparkles' : ($offering === null ? 'calendar' : ($row['addable'] ? 'book' : 'lock')));
                                            $iconTone = $row['selected']
                                                ? 'bg-emerald-100 text-emerald-600'
                                                : ($course === null
                                                    ? 'bg-brand-100 text-brand-600'
                                                    : ($offering === null
                                                        ? 'bg-slate-100 text-slate-400'
                                                        : ($row['awaitingPlan'] ? 'bg-amber-100 text-amber-600' : ($row['addable'] ? 'bg-brand-50 text-brand-600' : 'bg-rose-100 text-rose-500'))));
                                        @endphp
                                        <li data-filter-row
                                            class="flex flex-wrap items-center gap-3 rounded-2xl border p-3.5 transition
                                                {{ $row['selected'] ? 'border-emerald-200 bg-emerald-50/50' : ($row['addable'] ? 'border-slate-200/80 bg-white hover:border-brand-200' : 'border-slate-200/70 bg-slate-50/60') }}">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-xl {{ $iconTone }}">
                                                <x-icon :name="$icon" class="size-4" />
                                            </span>

                                            <div class="min-w-[15rem] flex-1">
                                                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-800">
                                                    {{ $course ? $course->code.' · '.$course->name : ($entry->placeholder_key ?: 'Elective slot') }}
                                                    @if ($course?->courseType)
                                                        <x-badge tone="slate">{{ $course->courseType->name }}</x-badge>
                                                    @endif
                                                    @if ($row['selected'])
                                                        <x-badge tone="emerald" dot>Selected</x-badge>
                                                    @endif
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    {{ $course ? $course->credits.' credits' : 'Placeholder slot' }}
                                                    @if ($offering)
                                                        · Section {{ $offering->section }}
                                                        @if ($offering->lecturer) · {{ $offering->lecturer->name }} @endif
                                                        @if ($offering->day) · {{ $offering->day }} {{ $offering->time }} @endif
                                                        @if ($offering->venue) · {{ $offering->venue }} @endif
                                                    @endif
                                                </p>
                                                @if ($row['reason'])
                                                    <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold {{ $offering === null ? 'text-slate-400' : ($row['awaitingPlan'] ? 'text-amber-700' : 'text-rose-600') }}">
                                                        <x-icon :name="$offering === null ? 'calendar' : ($row['awaitingPlan'] ? 'lock' : 'warning')" class="size-3.5" />
                                                        {{ $row['reason'] }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="ml-auto flex flex-wrap items-center gap-2">
                                                @if ($row['selected'])
                                                    @if ($row['registration']->status === 'pending' && ! $registrationLocked)
                                                        <form method="POST" action="{{ route('student.pre-registration.destroy', $row['registration']) }}"
                                                            data-confirm="Remove this pre-registration request?" data-scroll-keep>
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                                <x-icon name="x" class="size-4" />
                                                            </button>
                                                        </form>
                                                    @endif
                                                @elseif ($row['addable'])
                                                    <form method="POST" action="{{ route('student.pre-registration.store', $offering) }}" data-scroll-keep>
                                                        @csrf
                                                        <button class="btn-primary btn-sm">
                                                            <x-icon name="plus" class="size-3.5" /> Add
                                                        </button>
                                                    </form>
                                                @elseif ($offering)
                                                    <button class="btn-ghost btn-sm" disabled>
                                                        <x-icon name="lock" class="size-3.5" />
                                                        {{ $registrationLocked
                                                            ? ($submission->status === \App\Models\RegistrationSubmission::STATUS_APPROVED ? 'Approved' : 'Awaiting approval')
                                                            : ($row['awaitingPlan'] ? 'Awaiting approval' : 'Locked') }}
                                                    </button>
                                                @else
                                                    <span class="badge-slate">Not offered</span>
                                                @endif
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

    {{-- Registration cart --}}
    <div class="card card-pad mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-800">My Registration</h3>
            <div class="flex items-center gap-3">
                @if ($submission)
                    <x-badge :tone="match ($submission->status) {
                        'approved' => 'emerald',
                        'returned' => 'rose',
                        default => 'amber',
                    }" dot>
                        {{ match ($submission->status) {
                            'approved' => 'Approved by advisor',
                            'returned' => 'Returned by advisor',
                            default => 'Awaiting advisor approval',
                        } }}
                    </x-badge>
                @elseif ($planApproved)
                    <x-badge tone="slate">Not submitted yet</x-badge>
                @endif
                <x-badge tone="{{ $committedCredits >= $cap ? 'rose' : 'brand' }}">{{ $committedCredits }} cr</x-badge>
                <a href="{{ route('student.slip') }}" class="btn-subtle btn-sm">
                    <x-icon name="printer" class="size-3.5" /> Printable slip
                </a>
            </div>
        </div>

        <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full {{ $committedCredits >= $cap ? 'bg-rose-500' : 'bg-brand-500' }}"
                style="width: {{ min(100, ($committedCredits / $cap) * 100) }}%"></div>
        </div>
        <p class="mt-2 text-xs text-slate-400">
            {{ $remainingCredits }} credit(s) remaining before the {{ $cap }}-credit cap.
        </p>

        <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($registrations as $registration)
                @php
                    $tone = match ($registration->status) {
                        'approved' => 'emerald',
                        'rejected' => 'rose',
                        default => 'amber',
                    };
                @endphp
                <li class="rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $registration->offering->course->code }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $registration->offering->course->name }}</p>
                        </div>
                        <x-badge :tone="$tone">{{ ucfirst($registration->status) }}</x-badge>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500">{{ $registration->credits }} credits</span>
                        @if ($registration->status === 'pending' && ! $registrationLocked)
                            <form method="POST" action="{{ route('student.pre-registration.destroy', $registration) }}" data-scroll-keep>
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-semibold text-rose-500 hover:text-rose-700">Remove</button>
                            </form>
                        @endif
                    </div>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-xs text-slate-400 sm:col-span-2 xl:col-span-3">
                    You have not pre-registered for any course yet. Add a course from the list above.
                </li>
            @endforelse
        </ul>
    </div>
@endsection
