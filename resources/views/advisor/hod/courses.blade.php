@extends('layouts.app')

@section('title', 'Manage Courses')

@section('content')
    <x-page-header title="Manage Courses" subtitle="Course directory with pre-requisite, credit-hour and curriculum mapping">
        <x-slot:actions>
            <button type="button" data-modal-open="add-course" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New course
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

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
            <a href="{{ route($courseRoutes.'.index') }}" class="btn-ghost">Reset</a>
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
                        <th class="text-center">Credit hours</th>
                        <th class="text-center">Load</th>
                        <th class="text-center">Sem</th>
                        <th class="text-center">SC</th>
                        <th class="text-center">VBE</th>
                        <th class="text-center">SDG</th>
                        <th>Pre-requisite</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $course->course_code }}</p>
                                <p class="text-xs text-slate-400">{{ $course->course_name_en }}</p>
                                @if ($course->course_name_my)
                                    <p class="text-xs italic text-slate-400">{{ $course->course_name_my }}</p>
                                @endif
                            </td>
                            <td class="text-xs text-slate-500">{{ $course->program?->code ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $course->coursePlan?->code ?? '—' }}</td>
                            <td>
                                @if ($course->courseType)
                                    <x-badge tone="slate">{{ $course->courseType->name }}</x-badge>
                                @endif
                            </td>
                            <td class="text-center font-semibold">{{ $course->credit_hours }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->course_load ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->semester ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->mqfSc?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->vbe?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->sdg?->code ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $course->pre_requisite ?: '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-course"
                                        data-action="{{ route($courseRoutes.'.update', $course) }}"
                                        data-title="Edit {{ $course->course_code }}"
                                        data-field-course_code="{{ $course->course_code }}"
                                        data-field-course_name_en="{{ $course->course_name_en }}"
                                        data-field-course_name_my="{{ $course->course_name_my }}"
                                        data-field-course_type_code="{{ $course->courseType?->code }}"
                                        data-field-credit_hours="{{ $course->credit_hours }}"
                                        data-field-course_load="{{ $course->course_load }}"
                                        data-field-semester="{{ $course->semester }}"
                                        data-field-program_id="{{ $course->program_id }}"
                                        data-field-course_plan_id="{{ $course->course_plan_id }}"
                                        data-field-pre_requisite="{{ $course->pre_requisite }}"
                                        data-field-sc_code="{{ $course->mqfSc?->code }}"
                                        data-field-vbe_ept_code="{{ $course->vbe?->code }}"
                                        data-field-sdg_code="{{ $course->sdg?->code }}"
                                        data-field-alternative_group="{{ $course->alternative_group }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route($courseRoutes.'.destroy', $course) }}" data-confirm="Delete {{ $course->course_code }}?">
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
    <x-modal id="add-course" title="New course" size="lg" subtitle="Define course details, credit hours and pre-requisites">
        <form method="POST" action="{{ route($courseRoutes.'.store') }}">
            @csrf
            @include('advisor.hod.partials.course-fields', ['programs' => $programs, 'courseTypes' => $courseTypes, 'mqfs' => $mqfs, 'vbes' => $vbes, 'sdgs' => $sdgs, 'plans' => $plans])
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
            @include('advisor.hod.partials.course-fields', ['programs' => $programs, 'courseTypes' => $courseTypes, 'mqfs' => $mqfs, 'vbes' => $vbes, 'sdgs' => $sdgs, 'plans' => $plans])
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

@endsection
