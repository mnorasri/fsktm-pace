@extends('layouts.app')

@section('title', 'Students by PAK Advisor')

@section('content')
    <x-page-header title="Students by PAK Advisor"
        subtitle="Browse each advisor's advisees, then set a student's programme and study plan from the same screen">
        <x-slot:actions>
            <a href="{{ route('admin.students.index') }}" class="btn-ghost">
                <x-icon name="users" class="size-4" /> Student directory
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (! $selectedAdvisor && ! $showUnassigned)
        {{-- Advisor chooser --}}
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card label="PAK Advisors" :value="$advisors->count()" icon="users" tone="brand" />
            <x-stat-card label="Students" :value="$totalStudents" icon="academic-cap" tone="sky" />
            <x-stat-card label="Without an advisor" :value="$unassigned" icon="warning" tone="amber" />
        </div>

        <form method="GET" class="card card-pad mb-5 flex flex-wrap items-end gap-3">
            <div class="min-w-[16rem] flex-1">
                <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Search</span>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="search" value="{{ $search }}" placeholder="Advisor name or staff ID…" class="input pl-9">
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary">Search</button>
                @if ($search !== '')
                    <a href="{{ route('admin.students.by-advisor') }}" class="btn-ghost">Reset</a>
                @endif
            </div>
        </form>

        @if ($unassigned > 0)
            <a href="{{ route('admin.students.by-advisor', ['advisor' => 'none']) }}"
                class="mb-4 flex flex-wrap items-center gap-3 rounded-3xl border border-amber-200 bg-amber-50 p-4 transition hover:border-amber-300 hover:bg-amber-100/70">
                <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-amber-100 text-amber-600">
                    <x-icon name="warning" class="size-5" />
                </span>
                <div class="min-w-[14rem] flex-1">
                    <p class="text-sm font-bold text-amber-900">{{ $unassigned }} student(s) without a PAK advisor</p>
                    <p class="text-xs text-amber-800">Re-place their programme or plan, or assign an advisor from the directory.</p>
                </div>
                <span class="btn-ghost btn-sm">View</span>
            </a>
        @endif

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($advisors as $advisor)
                <a href="{{ route('admin.students.by-advisor', ['advisor' => $advisor->id, 'search' => $search !== '' ? $search : null]) }}"
                    class="card flex items-center gap-3 p-4 transition hover:border-brand-300 hover:bg-brand-50/30">
                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-sm font-bold text-white">
                        {{ strtoupper(substr($advisor->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $advisor->name }}</p>
                        <p class="truncate text-[11px] text-slate-400">
                            <span class="font-mono">{{ $advisor->staff_id }}</span>
                            @if ($advisor->department)
                                · {{ $advisor->department->code }}
                            @endif
                        </p>
                    </div>
                    <x-badge :tone="$advisor->advisees_count > 0 ? 'brand' : 'slate'">
                        {{ $advisor->advisees_count }} student(s)
                    </x-badge>
                </a>
            @empty
                <div class="sm:col-span-2 xl:col-span-3">
                    <x-empty-state title="No PAK advisor matches" icon="users"
                        message="Clear the search to list every advisor again." />
                </div>
            @endforelse
        </div>
    @else
        {{-- One advisor's students, with programme / plan editing --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.students.by-advisor', ['search' => $search !== '' ? $search : null]) }}"
                class="btn-ghost btn-sm">
                <x-icon name="arrow-left" class="size-4" /> All advisors
            </a>
            <x-badge tone="brand" dot>{{ $students->count() }} student(s)</x-badge>
        </div>

        <x-card pad>
            <div class="mb-4 flex flex-wrap items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-500 to-indigo-600 text-base font-bold text-white">
                    @if ($showUnassigned)
                        <x-icon name="warning" class="size-5" />
                    @else
                        {{ strtoupper(substr($selectedAdvisor->name, 0, 1)) }}
                    @endif
                </span>
                <div class="min-w-[16rem] flex-1">
                    <p class="text-base font-bold text-slate-900">
                        {{ $showUnassigned ? 'Students without a PAK advisor' : $selectedAdvisor->name }}
                    </p>
                    <p class="text-xs text-slate-400">
                        @if ($showUnassigned)
                            These students are not mapped to any advisor yet.
                        @else
                            <span class="font-mono">{{ $selectedAdvisor->staff_id }}</span>
                            @if ($selectedAdvisor->department)
                                · {{ $selectedAdvisor->department->name }}
                            @endif
                        @endif
                    </p>
                </div>
            </div>

            <p class="mb-4 rounded-2xl border border-slate-100 bg-slate-50/70 px-3.5 py-2.5 text-xs text-slate-500">
                Changing a programme also moves the student to that programme's department. A study plan must
                belong to the selected programme; choosing only a plan adopts its programme.
            </p>

            <div class="-mx-6 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Matric No.</th>
                            <th>Student</th>
                            <th>Programme</th>
                            <th>Study Plan</th>
                            <th>Plan Status</th>
                            <th class="text-right">Save</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php $formId = 'placement-'.$student->id; @endphp
                            <tr>
                                <td class="align-top font-mono text-xs font-semibold text-slate-600">
                                    {{ $student->student_id ?? '—' }}
                                </td>
                                <td class="align-top">
                                    <p class="font-semibold text-slate-800">{{ $student->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $student->email }}</p>
                                </td>
                                <td class="align-top">
                                    <form id="{{ $formId }}" method="POST"
                                        action="{{ route('admin.students.placement', $student) }}" data-scroll-keep>
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <select name="program_id" form="{{ $formId }}" class="select min-w-[9rem] py-1.5 text-xs"
                                        aria-label="Programme for {{ $student->name }}">
                                        <option value="">— Programme —</option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->id }}" @selected((int) $student->program_id === $program->id)>
                                                {{ $program->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="align-top">
                                    <select name="plan_id" form="{{ $formId }}" class="select min-w-[11rem] py-1.5 text-xs"
                                        aria-label="Study plan for {{ $student->name }}">
                                        <option value="">— Plan —</option>
                                        @foreach ($programs as $program)
                                            @if ($program->plans->isNotEmpty())
                                                <optgroup label="{{ $program->code }}">
                                                    @foreach ($program->plans as $plan)
                                                        <option value="{{ $plan->id }}" @selected((int) $student->plan_id === $plan->id)>
                                                            {{ $plan->code }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="align-top">
                                    @php
                                        $tone = ['approved' => 'emerald', 'submitted' => 'amber', 'rejected' => 'rose', 'draft' => 'slate'][$student->plan_status] ?? 'slate';
                                    @endphp
                                    <x-badge :tone="$tone">{{ ucfirst($student->plan_status) }}</x-badge>
                                </td>
                                <td class="align-top text-right">
                                    <button form="{{ $formId }}" class="btn-success btn-sm">
                                        <x-icon name="check" class="size-3.5" /> Save
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-slate-400">
                                    No students in this group.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
