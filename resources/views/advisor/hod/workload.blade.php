@extends('layouts.app')

@section('title', 'Lecturer Workload')

@section('content')
    <x-page-header title="Lecturer Load Dashboard"
        subtitle="Section and course load per lecturer across departments — load is each section's course load, plus {{ (int) $coordinatorLoad }} for coordinating a course · {{ $activeSession }} · Semester {{ $activeSemester }}">
        <x-slot:actions>
            <x-badge tone="brand" dot>Total sections: {{ $totalSections }}</x-badge>
            <x-badge tone="sky" dot>Total load: {{ $totalLoad }}</x-badge>
            <button type="button" onclick="window.print()" class="btn-primary no-print">
                <x-icon name="printer" class="size-4" /> Print
            </button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card card-pad no-print mb-5 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-[26rem]">
            <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Department</span>
            <select name="department_id" data-auto-submit class="select">
                <option value="">All departments</option>
                @foreach ($departmentOptions as $department)
                    <option value="{{ $department->id }}" @selected((int) request('department_id') === $department->id)>
                        {{ $department->code }} · {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button class="btn-primary">Filter</button>
            @if ($selectedDepartment)
                <a href="{{ route('advisor.offering.workload') }}" class="btn-ghost">Reset</a>
            @endif
        </div>
    </form>

    <div class="space-y-6">
        @forelse ($departments as $group)
            @php
                $dept = $group['department'];
                $sections = $group['lecturers']->sum('sections');
                $deptLoad = $group['lecturers']->sum('load');
            @endphp
            <x-card pad title="{{ $dept->code }}" subtitle="{{ $dept->name }}">
                <x-slot:actions>
                    <x-badge tone="slate">{{ $group['lecturers']->count() }} lecturers</x-badge>
                    <x-badge tone="brand">{{ $sections }} sections</x-badge>
                    <x-badge tone="sky">Load {{ $deptLoad }}</x-badge>
                </x-slot:actions>

                <div class="-mx-6 overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Lecturer</th>
                                <th class="text-center">Sections</th>
                                <th class="text-center">Distinct Courses</th>
                                <th class="text-center">Load</th>
                                <th class="text-center">Advisees</th>
                                <th>Courses</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($group['lecturers'] as $row)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-slate-800">{{ $row['lecturer']->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $row['lecturer']->staff_id }}</p>
                                    </td>
                                    <td class="text-center font-semibold text-slate-700">{{ $row['sections'] }}</td>
                                    <td class="text-center font-semibold text-slate-700">{{ $row['courses'] }}</td>
                                    <td class="text-center">
                                        <p class="font-semibold text-brand-700">{{ $row['load'] }}</p>
                                        @if ($row['coordinations']->isNotEmpty())
                                            <p class="text-[10px] font-semibold text-amber-600">
                                                incl. +{{ $row['coordinations']->count() }} coordinator
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-center font-semibold text-slate-700">{{ $row['advisees'] }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($row['assignments'] as $assignment)
                                                <span class="rounded-lg bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600"
                                                    title="{{ $assignment['pak'] ? 'PAK group: '.$assignment['pak'] : 'Whole course' }}">
                                                    {{ $assignment['code'] }}@if ($assignment['section']) (S{{ $assignment['section'] }})@endif
                                                </span>
                                            @empty
                                                @if ($row['coordinations']->isEmpty())
                                                    <span class="text-xs text-slate-400">No teaching load this term</span>
                                                @endif
                                            @endforelse

                                            @foreach ($row['coordinations'] as $assignment)
                                                <span class="rounded-lg bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                                    title="Coordinator of {{ $assignment['code'] }} · +{{ (int) $coordinatorLoad }} load">
                                                    {{ $assignment['code'] }} · Coordinator +{{ (int) $coordinatorLoad }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-sm text-slate-400">No lecturers in this department.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-empty-state title="No departments" icon="building" />
        @endforelse
    </div>
@endsection
