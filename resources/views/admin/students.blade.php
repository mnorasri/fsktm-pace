@extends('layouts.app')

@section('title', 'Manage Students')

@section('content')
    <x-page-header title="Manage Students" subtitle="Student directory with programme, plan and PAK advisor mapping">
        <x-slot:actions>
            <button type="button" data-modal-open="import-students" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-student" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New student
            </button>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or matric no…" class="input pl-9">
        </div>
        <select name="program_id" data-auto-submit class="select">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->code }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('admin.students.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Programme / Plan</th>
                        <th>PAK Advisor</th>
                        <th>Plan Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $student->name }}</p>
                                <p class="text-xs text-slate-400">{{ $student->student_id }} · {{ $student->email }}</p>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $student->program?->code ?? '—' }}
                                <span class="text-slate-300">/</span>
                                {{ $student->plan?->code ?? '—' }}
                            </td>
                            <td class="text-xs text-slate-500">{{ $student->advisor?->name ?? '—' }}</td>
                            <td>
                                @php
                                    $tone = ['approved' => 'emerald', 'submitted' => 'amber', 'rejected' => 'rose', 'draft' => 'slate'][$student->plan_status] ?? 'slate';
                                @endphp
                                <x-badge :tone="$tone">{{ ucfirst($student->plan_status) }}</x-badge>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-student"
                                        data-action="{{ route('admin.students.update', $student) }}"
                                        data-title="Edit {{ $student->name }}"
                                        data-field-name="{{ $student->name }}"
                                        data-field-email="{{ $student->email }}"
                                        data-field-student_id="{{ $student->student_id }}"
                                        data-field-program_id="{{ $student->program_id }}"
                                        data-field-plan_id="{{ $student->plan_id }}"
                                        data-field-advisor_id="{{ $student->advisor_id }}"
                                        data-field-phone="{{ $student->phone }}"
                                        data-field-status="{{ $student->status }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.students.reset-password', $student) }}" data-confirm="Reset password for {{ $student->name }}?">
                                        @csrf
                                        <button class="btn-subtle btn-sm"><x-icon name="lock" class="size-3.5" /> Reset</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}" data-confirm="Remove {{ $student->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-sm text-slate-400">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $students->links() }}</div>
    </x-card>

    @php
        $studentForm = function (bool $showStatus = false) use ($programs, $plans, $advisors) {
            return view('admin.partials.student-fields', [
                'programs' => $programs,
                'plans' => $plans,
                'advisors' => $advisors,
                'showStatus' => $showStatus,
            ])->render();
        };
    @endphp

    {{-- Add --}}
    <x-modal id="add-student" title="New student" size="lg">
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            {!! $studentForm() !!}
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create student</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-student" title="Edit student" size="lg">
        <form method="POST" action="">
            @csrf
            @method('PUT')
            {!! $studentForm(true) !!}
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-students" title="Bulk import students"
        subtitle="CSV columns: matric_num, student_name, student_email, program_code, plan_code, lecturer_id, student_phone">
        <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-mono">matric_num,student_name,student_email,program_code,plan_code,lecturer_id,student_phone</p>
                <p class="mt-2 font-mono">A210001,Aina Rahman,aina@student.fsktm.test,BIP,CS241-PLAN,LEC010,0123456789</p>
                <p class="mt-2"><span class="font-semibold">program_code</span> and <span class="font-semibold">plan_code</span> are matched by code, and <span class="font-semibold">lecturer_id</span> is the PAK advisor's lecturer ID.</p>
                <p class="mt-1">Rows are matched on <span class="font-semibold">matric_num</span> (or email). New accounts default to password <span class="font-semibold">password</span>.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
