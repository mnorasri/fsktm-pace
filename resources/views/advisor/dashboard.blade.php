@extends('layouts.app')

@section('title', 'My Supervisees')

@php
    $planTone = ['approved' => 'emerald', 'submitted' => 'amber', 'rejected' => 'rose', 'draft' => 'slate'];
@endphp

@section('content')
    <x-page-header title="My Supervisees"
        subtitle="Review study plans and approve course pre-registrations for your advisees">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Total Advisees" :value="$summary['advisees']" icon="users" tone="brand" />
        <x-stat-card label="Pending Study Plans" :value="$summary['pending_plans']" icon="calendar" tone="amber" hint="Awaiting your review" />
        <x-stat-card label="Registrations to Approve" :value="$summary['pending_registrations']" icon="book" tone="sky" hint="Students awaiting your decision" />
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($students as $row)
            @php($student = $row['student'])
            <details class="group card overflow-hidden">
                <summary class="cursor-pointer list-none transition hover:bg-slate-50/70">
                    <span class="flex flex-wrap items-center gap-4 p-5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-bold text-white">
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-800">{{ $student->name }}</span>
                            <span class="block truncate text-xs text-slate-400">
                                {{ $student->student_id }} · {{ $student->program?->code }} · {{ $student->plan?->code }}
                            </span>
                        </span>

                        <span class="hidden w-40 shrink-0 md:block">
                            <span class="flex items-center justify-between text-[11px] font-semibold text-slate-400">
                                <span>{{ $row['credits']['earned'] }} / {{ $row['credits']['required'] }} cr</span>
                                <span>{{ $row['credits']['percent'] }}%</span>
                            </span>
                            <span class="mt-1 block h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <span class="block h-full rounded-full bg-brand-500" style="width: {{ $row['credits']['percent'] }}%"></span>
                            </span>
                        </span>

                        <span class="flex shrink-0 items-center gap-2">
                            <x-badge :tone="$planTone[$student->plan_status] ?? 'slate'" dot>Plan: {{ ucfirst($student->plan_status) }}</x-badge>
                            @if ($row['submission']?->status === 'submitted')
                                <x-badge tone="amber" dot>Registration to approve</x-badge>
                            @elseif ($row['pending_registrations'] > 0)
                                <x-badge tone="amber">{{ $row['pending_registrations'] }} pending</x-badge>
                            @endif
                            <x-icon name="chevron-down" class="size-5 text-slate-400 transition group-open:rotate-180" />
                        </span>
                    </span>
                </summary>

                <div class="border-t border-slate-100 bg-slate-50/40 p-5">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                        {{-- Credit progression --}}
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400">Credit Progression</h4>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-2xl bg-white px-2 py-3 shadow-sm">
                                    <p class="text-lg font-bold text-slate-800">{{ $row['credits']['required'] }}</p>
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Required</p>
                                </div>
                                <div class="rounded-2xl bg-white px-2 py-3 shadow-sm">
                                    <p class="text-lg font-bold text-emerald-600">{{ $row['credits']['earned'] }}</p>
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Earned</p>
                                </div>
                                <div class="rounded-2xl bg-white px-2 py-3 shadow-sm">
                                    <p class="text-lg font-bold text-amber-600">{{ $row['credits']['remaining'] }}</p>
                                    <p class="text-[10px] font-semibold uppercase text-slate-400">Remaining</p>
                                </div>
                            </div>

                            {{-- Study plan approval --}}
                            @if ($student->plan_status === 'submitted')
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-3.5">
                                    <p class="text-xs font-bold text-amber-800">Study plan awaiting approval</p>
                                    <div class="mt-2 flex gap-2">
                                        <form method="POST" action="{{ route('advisor.plans.approve', $student) }}" class="flex-1">
                                            @csrf
                                            <button class="btn-success btn-sm w-full"><x-icon name="check" class="size-3.5" /> Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('advisor.plans.reject', $student) }}" class="flex-1">
                                            @csrf
                                            <button class="btn-danger btn-sm w-full"><x-icon name="x" class="size-3.5" /> Return</button>
                                        </form>
                                    </div>
                                </div>
                            @elseif ($student->plan_review_note)
                                <p class="mt-4 rounded-2xl bg-white px-3.5 py-3 text-xs text-slate-500 shadow-sm">
                                    <span class="font-semibold">Last note:</span> {{ $student->plan_review_note }}
                                </p>
                            @endif
                        </div>

                        {{-- Pre-registration requests --}}
                        <div class="xl:col-span-2">
                            @php($submission = $row['submission'])
                            @php($submitted = $submission?->status === 'submitted')
                            @php($termRequests = $row['term_registrations'])
                            @php($termCredits = $row['registration_totals']['credits'])

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400">Pre-Registration Requests</h4>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[11px] font-semibold text-slate-400">{{ $activeSession }} · Semester {{ $activeSemester }}</span>
                                    @if ($submission)
                                        <x-badge :tone="match ($submission->status) {
                                            'approved' => 'emerald',
                                            'returned' => 'rose',
                                            default => 'amber',
                                        }" dot>
                                            {{ $submission->status === 'submitted' ? 'Awaiting your approval' : ucfirst($submission->status) }}
                                            @if ($submission->submitted_at) · submitted {{ $submission->submitted_at->format('d M Y') }} @endif
                                        </x-badge>
                                    @else
                                        <x-badge tone="slate">Not submitted yet</x-badge>
                                    @endif
                                </div>
                            </div>

                            {{-- The term's courses, laid out like the printable slip. --}}
                            <div class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-slate-100 text-left uppercase tracking-wide text-slate-500">
                                            <th class="border-b border-slate-200 px-3 py-2">#</th>
                                            <th class="border-b border-slate-200 px-3 py-2">Course Code</th>
                                            <th class="border-b border-slate-200 px-3 py-2">Course Title</th>
                                            <th class="border-b border-slate-200 px-3 py-2 text-center">Section</th>
                                            <th class="border-b border-slate-200 px-3 py-2 text-center">Credits</th>
                                            <th class="border-b border-slate-200 px-3 py-2 text-center">Status</th>
                                            <th class="border-b border-slate-200 px-3 py-2 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($termRequests as $registration)
                                            <tr class="border-b border-slate-100 last:border-b-0">
                                                <td class="px-3 py-2 text-slate-400">{{ $loop->iteration }}</td>
                                                <td class="px-3 py-2 font-mono font-semibold text-slate-700">{{ $registration->offering->course->code }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $registration->offering->course->name }}</td>
                                                <td class="px-3 py-2 text-center text-slate-500">{{ $registration->offering->section }}</td>
                                                <td class="px-3 py-2 text-center font-semibold text-slate-700">{{ $registration->credits }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    <x-badge :tone="match ($registration->status) {
                                                        'approved' => 'emerald',
                                                        'rejected' => 'rose',
                                                        default => 'amber',
                                                    }">{{ ucfirst($registration->status) }}</x-badge>
                                                </td>
                                                <td class="px-3 py-2">
                                                    @if ($registration->status === 'pending' && ! $submitted)
                                                        <div class="flex justify-center gap-1.5">
                                                            <form method="POST" action="{{ route('advisor.registrations.approve', $registration) }}">
                                                                @csrf
                                                                <button class="rounded-lg p-1.5 text-emerald-500 transition hover:bg-emerald-50 hover:text-emerald-700"
                                                                    title="Approve {{ $registration->offering->course->code }}">
                                                                    <x-icon name="check" class="size-4" />
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="{{ route('advisor.registrations.reject', $registration) }}">
                                                                @csrf
                                                                <button class="rounded-lg p-1.5 text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"
                                                                    title="Reject {{ $registration->offering->course->code }}">
                                                                    <x-icon name="x" class="size-4" />
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @else
                                                        <span class="block text-center text-slate-300">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-3 py-6 text-center text-slate-400">
                                                    No pre-registration requests for {{ $activeSession }} · Semester {{ $activeSemester }}.
                                                </td>
                                            </tr>
                                        @endforelse
                                        <tr class="bg-slate-50 font-semibold">
                                            <td colspan="4" class="px-3 py-2 text-right text-slate-500">Total Credits</td>
                                            <td class="px-3 py-2 text-center text-slate-800">{{ $termCredits }}</td>
                                            <td colspan="2" class="px-3 py-2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="mt-2 text-xs text-slate-400">
                                Maximum load per term: <span class="font-semibold text-slate-500">{{ \App\Services\CurriculumService::MAX_CREDITS_PER_TERM }} credits</span> ·
                                Committed load: <span class="font-semibold text-slate-500">{{ $termCredits }} credits</span>
                            </p>

                            @if ($submitted)
                                <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-3.5">
                                    <p class="w-full text-xs font-bold text-amber-800">
                                        Approve the whole registration ({{ $row['registration_totals']['courses'] }} course(s) confirmed at once),
                                        or return it to {{ $student->name }} for revision.
                                    </p>
                                    <form method="POST" action="{{ route('advisor.registrations.batch.approve', $student) }}" data-scroll-keep>
                                        @csrf
                                        <button class="btn-success btn-sm"><x-icon name="check" class="size-3.5" /> Approve registration</button>
                                    </form>
                                    <form method="POST" action="{{ route('advisor.registrations.batch.return', $student) }}"
                                        class="flex min-w-[15rem] flex-1 items-center gap-2" data-scroll-keep>
                                        @csrf
                                        <input type="text" name="note" placeholder="Reason (optional)" class="input flex-1">
                                        <button class="btn-ghost btn-sm"><x-icon name="arrow-path" class="size-3.5" /> Return</button>
                                    </form>
                                </div>
                            @elseif ($submission?->note)
                                <p class="mt-3 rounded-2xl bg-white px-3.5 py-3 text-xs text-slate-500 shadow-sm">
                                    <span class="font-semibold">Your note:</span> {{ $submission->note }}
                                </p>
                            @endif

                            @if ($row['other_registrations']->isNotEmpty())
                                <p class="mt-3 text-xs text-slate-400">
                                    Also recorded in other terms:
                                    <span class="font-medium text-slate-500">
                                        {{ $row['other_registrations']->map(fn ($r) => $r->offering->course->code.' · '.$r->session.' Sem '.$r->semester.' ('.ucfirst($r->status).')')->implode(', ') }}
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Study plan: the same per-year odd / even grid the student sees --}}
                    <div class="mt-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-400">Study Plan</h4>
                            @if (\count($row['warnings']) > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold text-amber-700">
                                    <x-icon name="warning" class="size-3" />
                                    {{ \count($row['warnings']) }} prerequisite violation(s)
                                </span>
                            @endif
                        </div>

                        @if (! $row['has_plan'])
                            <p class="mt-3 rounded-2xl bg-white px-4 py-6 text-center text-xs text-slate-400 shadow-sm">
                                This student has not built a study plan yet.
                            </p>
                        @else
                            @php($hasSpecial = \in_array(3, $row['semesters'], true))

                            <div class="mt-3 space-y-5">
                                @foreach ($row['years'] as $year => $terms)
                                    <section class="space-y-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="grid size-8 place-items-center rounded-xl bg-slate-900 text-[11px] font-bold text-white">Y{{ $year }}</span>
                                            <h5 class="text-sm font-bold text-slate-700">Year {{ $year }}</h5>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 {{ $hasSpecial ? 'lg:grid-cols-3' : 'lg:grid-cols-2' }}">
                                            @foreach ($row['semesters'] as $semester)
                                                @php($term = $terms[$semester])
                                                <div class="rounded-2xl bg-white p-4 shadow-sm">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <p class="text-xs font-bold text-slate-600">{{ $semesterLabels[$semester] }}</p>
                                                        <span class="text-[11px] font-semibold text-slate-400">
                                                            {{ $term['entries']->count() }} course(s) · {{ $term['credits'] }} cr
                                                        </span>
                                                    </div>

                                                    @if ($term['entries']->isEmpty())
                                                        <p class="mt-3 rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-[11px] text-slate-400">
                                                            No courses in this term
                                                        </p>
                                                    @else
                                                        <ul class="mt-3 space-y-2">
                                                            @foreach ($term['entries'] as $entry)
                                                                @php($course = $entry->course)
                                                                @php($isPlaceholder = $course === null)
                                                                {{-- A chosen "Elective Course" (CT07) replaced the
                                                                     "Elective Option" slot it was picked for — keep
                                                                     the slot's name visible for the advisor. --}}
                                                                @php($previousElective = (! $isPlaceholder && $entry->isElectiveCourse() && $entry->hasElectiveChoice()) ? $entry->electiveSlotCourse() : null)
                                                                @php($late = $row['warnings'][$entry->id] ?? [])
                                                                @php($dot = match ($entry->status) {
                                                                    'COMPLETED' => 'bg-emerald-500',
                                                                    'EXEMPTED' => 'bg-amber-500',
                                                                    default => 'bg-sky-500',
                                                                })
                                                                <li class="flex flex-wrap items-start gap-2.5 rounded-xl border border-slate-200/70 bg-slate-50/60 p-3">
                                                                    <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-lg {{ $isPlaceholder ? 'bg-brand-100 text-brand-600' : 'bg-white text-slate-400 shadow-sm' }}">
                                                                        <x-icon :name="$isPlaceholder ? 'sparkles' : 'book'" class="size-3.5" />
                                                                    </span>

                                                                    <div class="min-w-[13rem] flex-1">
                                                                        <p class="flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-800">
                                                                            {{ $isPlaceholder ? ($entry->placeholder_key ?: 'Elective slot') : $course->code.' · '.$course->name }}
                                                                            @if (! $isPlaceholder && $entry->placeholder_key)
                                                                                <x-badge tone="brand">Elective</x-badge>
                                                                            @endif
                                                                            @if ($previousElective)
                                                                                <x-badge tone="sky"
                                                                                    title="The elective course chosen for the {{ $previousElective->name }} slot ({{ $previousElective->code }}) — this row previously held that Elective Option">
                                                                                    Previously: {{ $previousElective->name }}
                                                                                </x-badge>
                                                                            @endif
                                                                            @if ($course?->alternative_group)
                                                                                <x-badge tone="brand">{{ $course->alternative_group }}</x-badge>
                                                                            @endif
                                                                            @if (! empty($late))
                                                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                                                                    <x-icon name="warning" class="size-3" />
                                                                                    Prereq misplaced: {{ collect($late)->pluck('code')->implode(', ') }}
                                                                                </span>
                                                                            @endif
                                                                        </p>
                                                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                                                            @if ($isPlaceholder)
                                                                                Elective placeholder — replace with a real course
                                                                            @else
                                                                                {{ $course->credits }} credits
                                                                                @if ($course->courseType)
                                                                                    · {{ $course->courseType->name }}
                                                                                @endif
                                                                                @if ($course->prerequisites)
                                                                                    · Prereq: {{ $course->prerequisites }}
                                                                                @endif
                                                                            @endif
                                                                        </p>
                                                                    </div>

                                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                                                        <span class="size-1.5 rounded-full {{ $dot }}"></span>
                                                                        {{ $entry->status }}
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </details>
        @empty
            <x-empty-state title="No supervisees yet" icon="users"
                message="Students assigned to you as their academic advisor will appear here." />
        @endforelse
    </div>
@endsection
