@extends('layouts.app')

@section('title', $mode === 'hod' ? 'Course Request Approvals' : 'Course Requests')

@section('content')
    <x-page-header :title="$mode === 'hod' ? 'Course Request Approvals' : 'Course Requests'"
        subtitle="Student request → academic advisor review → Head of Department approval">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
            @if ($mode === 'student')
                <a href="{{ route('student.pre-registration') }}" class="btn-ghost">Pre-Registration</a>
            @elseif ($mode === 'hod')
                <a href="{{ route('advisor.offering.manage') }}" class="btn-ghost">Manage Offering</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
        Only advisor-forwarded requests reach the Head of Department. Final approval makes the course offered
        and automatically registers the requesting student with approved status for that term.
        The registration appears in Manage Offering, the student's registration list and registration slip.
        Prerequisites are highlighted for review, but do not block registration. The 20-credit limit still applies.
    </div>

    @if ($requestsClosed)
        <p class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            The registration deadline has passed. New requests and final approvals are closed for this term.
        </p>
    @endif

    @if ($mode === 'student')
        <p class="mb-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
            <span class="font-semibold">Committed: {{ $committedCredits }} / 20 credits</span>
            — pre-registered courses plus outstanding course requests. Rejected requests do not reserve credits.
        </p>
        <x-card class="mb-6" title="Request an unoffered course" subtitle="One request per course per term. Explain why you need the course.">
            @if (! auth()->user()->advisor_id)
                <p class="text-sm text-amber-700">You need an assigned academic advisor before requesting a course.</p>
            @elseif ($courses->isEmpty())
                <p class="text-sm text-slate-500">There are no unrequested, incomplete, unoffered courses in your study plan.</p>
            @else
                <form method="POST" action="{{ route('student.course-requests.store') }}" class="space-y-4">
                    @csrf
                    <label class="block">
                        <span class="label">Course from your study plan</span>
                        <select name="course_id" class="select" required @disabled($requestsClosed)>
                            <option value="">Choose a course…</option>
                            @foreach ($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id', request('course_id')) === (string) $course->id)>
                                    {{ $course->code }} · {{ $course->name }} · {{ $course->credits }} credits
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="label">Reason for requesting this course (required)</span>
                        <textarea name="reason" class="input" rows="3" maxlength="2000" required
                            placeholder="For example: this course is needed to graduate on time." @disabled($requestsClosed)>{{ old('reason') }}</textarea>
                    </label>
                    <button class="btn-primary" @disabled($requestsClosed)>
                        <x-icon name="plus" class="size-4" /> Send to advisor
                    </button>
                </form>
            @endif
        </x-card>
    @endif

    <div class="space-y-4">
        @forelse ($requests as $record)
            @php
                $isActiveTerm = $record->session === $activeSession && $record->semester === $activeSemester;
                $advisorAction = $mode === 'advisor' && $record->status === 'pending_advisor' && $isActiveTerm;
                $hodAction = $mode === 'hod' && $record->status === 'pending_hod' && $isActiveTerm;
                $isRegistered = \in_array($record->id, $registeredRequestIds, true);
                $tone = match ($record->status) {
                    'approved' => $isRegistered ? 'emerald' : 'amber',
                    'rejected' => 'rose',
                    'pending_hod' => 'sky',
                    default => 'amber',
                };
            @endphp
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="break-words text-base font-bold text-slate-800">{{ $record->course->code }} · {{ $record->course->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $record->session }} · Semester {{ $record->semester }} ·
                            Requested {{ $record->created_at?->format('d M Y, H:i') }}
                        </p>
                        @if ($mode !== 'student')
                            <p class="mt-2 text-sm text-slate-600">
                                {{ $record->student->name }} · {{ $record->student->student_id }}
                                @if ($record->student->program) · {{ $record->student->program->code }} @endif
                            </p>
                        @endif
                    </div>
                    <x-badge :tone="$tone" dot>{{ $record->statusLabel($isRegistered) }}</x-badge>
                </div>
                @if ($record->status === 'approved' && ! $isRegistered)
                    <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                        Approval is recorded, but this course has no approved registration yet.
                        Contact the academic advisor to resolve any registration or credit-limit issues.
                    </p>
                @endif

                <x-prerequisite-indicator :prerequisites="$requestPrerequisites[$record->id]" />
                <div class="mt-4 rounded-xl bg-slate-50 p-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Student's reason</p>
                    <p class="mt-1 whitespace-pre-line break-words text-sm text-slate-700">{{ $record->reason }}</p>
                </div>

                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-xs font-semibold text-slate-600">1. Academic advisor</p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($record->advisor_reviewed_at)
                                {{ $record->advisorReviewer?->name ?? 'Advisor' }} · {{ $record->advisor_reviewed_at->format('d M Y, H:i') }}
                                · {{ $record->forwarded_at ? 'Forwarded to Head of Department' : 'Rejected' }}
                            @else
                                Awaiting {{ $record->student->advisor?->name ?? 'academic advisor' }}
                            @endif
                        </p>
                        @if ($record->advisor_note)
                            <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-700">{{ $record->advisor_note }}</p>
                        @endif
                    </div>
                    <div class="rounded-xl border border-slate-100 p-3">
                        <p class="text-xs font-semibold text-slate-600">2. Head of Department</p>
                        <p class="mt-1 text-xs text-slate-500">
                            @if ($record->hod_reviewed_at)
                                {{ $record->hodReviewer?->name ?? 'Head of Department' }} · {{ $record->hod_reviewed_at->format('d M Y, H:i') }}
                                · {{ $record->status === 'approved' ? 'Approved' : 'Rejected' }}
                            @elseif ($record->forwarded_at)
                                Awaiting final decision
                            @else
                                Advisor forwarding required first
                            @endif
                        </p>
                        @if ($record->hod_note)
                            <p class="mt-2 whitespace-pre-line break-words text-sm text-slate-700">{{ $record->hod_note }}</p>
                        @endif
                    </div>
                </div>

                @if ($advisorAction || $hodAction)
                    <form method="POST" action="{{ route($advisorAction ? 'advisor.course-requests.review' : 'advisor.course-requests.hod.review', $record) }}"
                        class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                        @csrf
                        <label class="block">
                            <span class="label">Review reason <span class="font-normal text-slate-400">{{ $advisorAction ? '(required for forwarding or rejecting)' : '(required when rejecting)' }}</span></span>
                            <textarea name="note" rows="2" maxlength="2000" class="input" @required($advisorAction) placeholder="Explain your decision for the student and the next reviewer."></textarea>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @if ($advisorAction)
                                <button name="decision" value="forward" class="btn-primary">Forward to Head of Department</button>
                            @else
                                <button name="decision" value="approve" class="btn-success" @disabled($requestsClosed)>Approve &amp; register student</button>
                            @endif
                            <button name="decision" value="reject" class="btn-danger" data-confirm="Reject this course request?">Reject request</button>
                        </div>
                    </form>
                @endif
            </x-card>
        @empty
            <x-empty-state title="No course requests" icon="clipboard"
                :message="$mode === 'hod' ? 'Requests will appear here after an academic advisor forwards them.' : 'No course requests have been submitted yet.'" />
        @endforelse
    </div>
    <div class="mt-5">{{ $requests->links() }}</div>
@endsection
