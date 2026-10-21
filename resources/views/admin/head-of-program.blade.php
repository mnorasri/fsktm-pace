@extends('layouts.app')

@section('title', 'Manage Head of Program')

@section('content')
    <x-page-header title="Manage Head of Program"
        subtitle="Select a department, choose a programme, then assign its head from the department's lecturers" />

    {{-- Step 1 — department, Step 2 — programme --}}
    <form method="GET" class="card card-pad grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="label">1 · Department</span>
            <select name="department_id" data-auto-submit class="select">
                <option value="">— Select a department —</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($selectedDepartment?->id === $department->id)>{{ $department->code }} — {{ $department->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="label">2 · Programme</span>
            <select name="program_id" data-auto-submit class="select" @disabled(! $selectedDepartment)>
                <option value="">{{ $selectedDepartment ? '— Select a programme —' : 'Select a department first' }}</option>
                @foreach ($programs as $program)
                    <option value="{{ $program->id }}" @selected($selectedProgram?->id === $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                @endforeach
            </select>
        </label>
    </form>

    @if (! $selectedDepartment)
        <x-card class="mt-6" pad>
            <div class="py-10 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                    <x-icon name="academic-cap" class="size-6" />
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">Select a department to begin</p>
                <p class="mt-1 text-xs text-slate-400">Its programmes and lecturers will appear here.</p>
            </div>
        </x-card>
    @else
        {{-- Programmes of the department and their current head --}}
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($programs as $program)
                <div class="card card-pad">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $program->code }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $program->name }}</p>
                        </div>
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $program->head_of_program_id ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' }}">
                            <x-icon name="academic-cap" class="size-5" />
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        @if ($program->headOfProgram)
                            <x-badge tone="emerald" dot>{{ $program->headOfProgram->name }}</x-badge>
                            <form method="POST" action="{{ route('admin.head-of-program.revoke', $program) }}"
                                data-confirm="Remove {{ $program->headOfProgram->name }} as Head of Program for {{ $program->code }}?">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost btn-sm"><x-icon name="x" class="size-3.5" /> Revoke</button>
                            </form>
                        @else
                            <x-badge tone="slate" dot>Not assigned</x-badge>
                            <a href="{{ route('admin.head-of-program.index', ['department_id' => $selectedDepartment->id, 'program_id' => $program->id]) }}"
                                class="btn-subtle btn-sm"><x-icon name="plus" class="size-3.5" /> Assign</a>
                        @endif
                    </div>
                </div>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3" pad>
                    <p class="py-8 text-center text-sm text-slate-400">This department has no programmes yet.</p>
                </x-card>
            @endforelse
        </div>

        {{-- Step 3 — lecturers of the department --}}
        <x-card class="mt-6" pad title="Lecturers in {{ $selectedDepartment->code }}"
            subtitle="{{ $selectedProgram ? 'Assign the Head of Program for '.$selectedProgram->code : 'Choose a programme above to enable assignment' }}">
            <div class="-mx-6 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lecturer</th>
                            <th>Head of Program</th>
                            <th class="text-right">Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lecturers as $lecturer)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $lecturer->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $lecturer->staff_id ?? '—' }} · {{ $lecturer->email }}</p>
                                </td>
                                <td class="text-xs text-slate-500">{{ $lecturer->headedPrograms->pluck('code')->implode(', ') ?: '—' }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        @if (! $selectedProgram)
                                            <span class="text-xs text-slate-400">Select a programme</span>
                                        @elseif ($selectedProgram->head_of_program_id === $lecturer->id)
                                            <x-badge tone="emerald" dot>Head of {{ $selectedProgram->code }}</x-badge>
                                        @else
                                            <form method="POST" action="{{ route('admin.head-of-program.assign') }}">
                                                @csrf
                                                <input type="hidden" name="program_id" value="{{ $selectedProgram->id }}">
                                                <input type="hidden" name="lecturer_id" value="{{ $lecturer->id }}">
                                                <button class="btn-primary btn-sm"><x-icon name="check" class="size-3.5" /> Assign</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-8 text-center text-sm text-slate-400">No lecturers in this department.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
@endsection
