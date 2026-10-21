@extends('layouts.app')

@section('title', 'Manage Courses')

@section('content')
    <x-page-header title="Manage Courses" subtitle="Course directory with prerequisite, credit and curriculum mapping">
        <x-slot:actions>
            <button type="button" data-modal-open="import-courses" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-course" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New course
            </button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <form method="GET" class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search course code or title…" class="input pl-9">
        </div>
        <select name="program_id" data-auto-submit class="select">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->code }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('admin.courses.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Programme</th>
                        <th>Plan</th>
                        <th>Type</th>
                        <th class="text-center">Credits</th>
                        <th class="text-center">Load</th>
                        <th class="text-center">Sem</th>
                        <th class="text-center">SC</th>
                        <th class="text-center">VBE</th>
                        <th class="text-center">SDG</th>
                        <th>Prerequisites</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $course->code }}</p>
                                <p class="text-xs text-slate-400">{{ $course->name }}</p>
                            </td>
                            <td class="text-xs text-slate-500">{{ $course->program?->code ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $course->coursePlan?->code ?? '—' }}</td>
                            <td>
                                @if ($course->courseType)
                                    <x-badge tone="slate">{{ $course->courseType->name }}</x-badge>
                                @endif
                            </td>
                            <td class="text-center font-semibold">{{ $course->credits }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->course_load ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->semester ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->mqfSc?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->vbe?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->sdg?->code ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $course->prerequisites ?: '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-course"
                                        data-action="{{ route('admin.courses.update', $course) }}"
                                        data-title="Edit {{ $course->code }}"
                                        data-field-code="{{ $course->code }}"
                                        data-field-name="{{ $course->name }}"
                                        data-field-course_type_id="{{ $course->course_type_id }}"
                                        data-field-credits="{{ $course->credits }}"
                                        data-field-course_load="{{ $course->course_load }}"
                                        data-field-semester="{{ $course->semester }}"
                                        data-field-program_id="{{ $course->program_id }}"
                                        data-field-course_plan_id="{{ $course->course_plan_id }}"
                                        data-field-prerequisites="{{ $course->prerequisites }}"
                                        data-field-mqf_sc_id="{{ $course->mqf_sc_id }}"
                                        data-field-vbe_id="{{ $course->vbe_id }}"
                                        data-field-sdg_id="{{ $course->sdg_id }}"
                                        data-field-alternative_group="{{ $course->alternative_group }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" data-confirm="Delete {{ $course->code }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="py-8 text-center text-sm text-slate-400">No courses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $courses->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-course" title="New course" size="lg" subtitle="Define course details, credits and prerequisites">
        <form method="POST" action="{{ route('admin.courses.store') }}">
            @csrf
            @include('admin.partials.course-fields', ['programs' => $programs, 'courseTypes' => $courseTypes, 'mqfs' => $mqfs, 'vbes' => $vbes, 'sdgs' => $sdgs, 'plans' => $plans])
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create course</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-course" title="Edit course" size="lg">
        <form method="POST" action="">
            @csrf
            @method('PUT')
            @include('admin.partials.course-fields', ['programs' => $programs, 'courseTypes' => $courseTypes, 'mqfs' => $mqfs, 'vbes' => $vbes, 'sdgs' => $sdgs, 'plans' => $plans])
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-courses" title="Bulk import courses" subtitle="CSV columns: course_code, course_name, course_type, credits, course_load, semester, program_code, plan_code, prerequisites, sc_code, vbe_code, sdg_code, alternative_group">
        <form method="POST" action="{{ route('admin.courses.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono">course_code,course_name,course_type,credits,course_load,semester,program_code,plan_code,prerequisites,sc_code,vbe_code,sdg_code,alternative_group</p>
                <p class="mt-1"><span class="font-semibold">course_type</span>, <span class="font-semibold">sc_code</span>, <span class="font-semibold">vbe_code</span>, <span class="font-semibold">program_code</span>, <span class="font-semibold">plan_code</span> and <span class="font-semibold">sdg_code</span> are matched by code.</p>
                <p class="mt-1">Codes are matched case-insensitively (with a prefix fallback, e.g. <span class="font-mono">BIM20252026-LOC</span> → <span class="font-mono">BIM20252026-LOCAL</span>). Any code still unmatched is created in Manage Configs automatically.</p>
                <p class="mt-1"><span class="font-semibold">alternative_group</span> is optional — courses sharing a value are mutually exclusive in the study plan. Leave the column out to keep each course's current value.</p>
                <p class="mt-1">Existing course codes are updated, new codes are created.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
