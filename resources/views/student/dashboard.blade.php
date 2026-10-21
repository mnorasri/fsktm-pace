@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <x-page-header title="Welcome back, {{ str($student->name)->before(' ') }}"
        subtitle="{{ $student->program?->name }} · {{ $student->plan?->name }}">
        <x-slot:actions>
            <x-badge tone="brand" dot>{{ $activeSession }} · Semester {{ $activeSemester }}</x-badge>
            <a href="{{ route('student.study-plan') }}" class="btn-ghost">
                <x-icon name="calendar" class="size-4" /> Study Plan
            </a>
            <a href="{{ route('student.pre-registration') }}" class="btn-primary">
                <x-icon name="plus" class="size-4" /> Pre-Register
            </a>
        </x-slot:actions>
    </x-page-header>

    {{-- Credit status dashboard --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Required Credits" :value="$credits['required']" icon="academic-cap" tone="brand" hint="Curriculum total" />
        <x-stat-card label="Earned Credits" :value="$credits['earned']" icon="check" tone="emerald" hint="{{ $credits['percent'] }}% of programme" />
        <x-stat-card label="Planned Credits" :value="$credits['planned']" icon="calendar" tone="sky" hint="In your study plan" />
        <x-stat-card label="Remaining Credits" :value="$credits['remaining']" icon="chart" tone="amber" hint="To graduate" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Progress + plan status --}}
        <div class="space-y-6 xl:col-span-2">
            <x-card title="Credit Progression" subtitle="Progress towards your graduation requirement" pad>
                <x-slot:actions>
                    <a href="{{ route('student.slip') }}" class="btn-ghost btn-sm">
                        <x-icon name="printer" class="size-3.5" /> Slip
                    </a>
                </x-slot:actions>

                <div class="flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">{{ $credits['earned'] }} earned</span>
                    <span class="font-semibold text-slate-400">{{ $credits['required'] }} required</span>
                </div>
                <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-indigo-600 transition-all"
                        style="width: {{ $credits['percent'] }}%"></div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-2xl bg-emerald-50 px-3 py-3">
                        <p class="text-lg font-bold text-emerald-700">{{ $credits['earned'] }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-600">Earned</p>
                    </div>
                    <div class="rounded-2xl bg-sky-50 px-3 py-3">
                        <p class="text-lg font-bold text-sky-700">{{ $credits['planned'] }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-sky-600">Planned</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 px-3 py-3">
                        <p class="text-lg font-bold text-amber-700">{{ $credits['remaining'] }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-600">Remaining</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Next Planned Semester" subtitle="The next term you have planned courses for">
                @if ($upcoming->isEmpty())
                    <x-empty-state title="No planned courses" icon="calendar"
                        message="Add courses to your study plan to see your upcoming semester here." />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($upcoming as $entry)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">
                                        {{ $entry->course->code }} · {{ $entry->course->name }}
                                    </p>
                                    <p class="text-xs text-slate-400">{{ $entry->course->credits }} credits</p>
                                </div>
                                <x-badge tone="slate">Year {{ $entry->year }} Sem {{ $entry->semester }}</x-badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">
            {{-- Study plan approval status --}}
            <x-card title="Study Plan Status">
                @php
                    $tone = match ($student->plan_status) {
                        'approved' => 'emerald',
                        'submitted' => 'amber',
                        'rejected' => 'rose',
                        default => 'slate',
                    };
                    $label = ucfirst($student->plan_status);
                @endphp
                <div class="flex items-center justify-between">
                    <x-badge :tone="$tone" dot>{{ $label }}</x-badge>
                    @if ($student->plan_submitted_at)
                        <span class="text-xs text-slate-400">Submitted {{ $student->plan_submitted_at->diffForHumans() }}</span>
                    @endif
                </div>

                @if ($student->plan_review_note)
                    <p class="mt-3 rounded-2xl bg-slate-50 px-3.5 py-3 text-sm text-slate-600">
                        <span class="font-semibold">Advisor note:</span> {{ $student->plan_review_note }}
                    </p>
                @endif

                @if (in_array($student->plan_status, ['draft', 'rejected']))
                    <form method="POST" action="{{ route('student.study-plan.submit') }}" class="mt-4">
                        @csrf
                        <button class="btn-primary w-full">
                            <x-icon name="upload" class="size-4" /> Submit for approval
                        </button>
                    </form>
                @elseif ($student->plan_status === 'submitted')
                    <p class="mt-4 rounded-2xl bg-amber-50 px-3.5 py-3 text-xs font-medium text-amber-700">
                        Awaiting your advisor's review.
                    </p>
                @endif
            </x-card>

            {{-- Active term registration --}}
            <x-card title="Active Term" subtitle="Pre-registration for {{ $activeSession }}">
                <div class="mb-4 flex items-baseline justify-between">
                    <span class="text-3xl font-bold text-slate-800">{{ $committedCredits }}<span class="text-base font-medium text-slate-400">/{{ $creditCap }}</span></span>
                    <span class="text-xs font-semibold text-slate-400">credits committed</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $committedCredits >= $creditCap ? 'bg-rose-500' : 'bg-brand-500' }}"
                        style="width: {{ min(100, $creditCap > 0 ? ($committedCredits / $creditCap) * 100 : 0) }}%"></div>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Requests this term</dt>
                        <dd class="font-semibold text-slate-700">{{ $registrations->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Courses offered</dt>
                        <dd class="font-semibold text-slate-700">{{ $availableOfferings }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Advisor approval</dt>
                        <dd>
                            @if ($submission?->status === 'approved')
                                <x-badge tone="emerald" dot>Approved</x-badge>
                            @elseif ($submission?->status === 'submitted')
                                <x-badge tone="amber" dot>Awaiting advisor</x-badge>
                            @elseif ($submission?->status === 'returned')
                                <x-badge tone="rose" dot>Returned</x-badge>
                            @elseif ($registrations->isNotEmpty())
                                <x-badge tone="slate">Not submitted</x-badge>
                            @else
                                <span class="text-xs font-semibold text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <a href="{{ route('student.pre-registration') }}" class="btn-subtle mt-4 w-full">
                    <x-icon name="book" class="size-4" /> Manage pre-registration
                </a>
            </x-card>

            @if ($warnings->isNotEmpty())
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
                    <div class="flex items-center gap-2 text-amber-700">
                        <x-icon name="warning" class="size-5" />
                        <p class="text-sm font-bold">Prerequisite warnings</p>
                    </div>
                    <ul class="mt-2 space-y-1.5 text-xs text-amber-800">
                        @foreach ($warnings as $entryId => $late)
                            @foreach ($late as $item)
                                <li>{{ $item['code'] }} must be completed before this course (currently planned in {{ $item['term'] }}).</li>
                            @endforeach
                        @endforeach
                    </ul>
                    <a href="{{ route('student.study-plan') }}" class="mt-3 inline-block text-xs font-bold text-amber-700 underline">
                        Review study plan
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
