@extends('layouts.app')

@section('title', 'Courses')

@section('content')
    <x-page-header title="Courses"
        subtitle="Course directory with credit hours, course type and SC / VBE EPT / SDG mapping">
        <x-slot:actions>
            <button type="button" data-modal-open="import-courses" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-course" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New course
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    <form method="GET" data-scroll-keep class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search course code, English or Malay title…" class="input pl-9">
        </div>
        <select name="course_type_code" data-auto-submit class="select">
            <option value="">All course types</option>
            @foreach ($courseTypes as $type)
                <option value="{{ $type->code }}" @selected(request('course_type_code') === $type->code)>{{ $type->code }} — {{ $type->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('curriculum.courses.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Type</th>
                        <th class="text-center">Credit hours</th>
                        <th class="text-center">Load</th>
                        <th class="text-center">SC</th>
                        <th class="text-center">VBE EPT</th>
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
                                <p class="text-xs text-slate-500">{{ $course->course_name_en }}</p>
                                @if ($course->course_name_my)
                                    <p class="text-xs italic text-slate-400">{{ $course->course_name_my }}</p>
                                @endif
                            </td>
                            <td>
                                @if ($course->courseType)
                                    <x-badge tone="slate">{{ $course->courseType->code }}</x-badge>
                                    <span class="ml-1 text-xs text-slate-400">{{ $course->courseType->name }}</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-center font-semibold">{{ $course->credit_hours }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->course_load ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->mqfSc?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->vbe?->code ?? '—' }}</td>
                            <td class="text-center text-xs text-slate-500">{{ $course->sdg?->code ?? '—' }}</td>
                            <td class="text-xs text-slate-500">{{ $course->pre_requisite ?: '—' }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-course"
                                        data-action="{{ route('curriculum.courses.update', $course) }}"
                                        data-title="Edit {{ $course->course_code }}"
                                        data-field-course_code="{{ $course->course_code }}"
                                        data-field-course_name_en="{{ $course->course_name_en }}"
                                        data-field-course_name_my="{{ $course->course_name_my }}"
                                        data-field-credit_hours="{{ $course->credit_hours }}"
                                        data-field-course_type_code="{{ $course->courseType?->code }}"
                                        data-field-course_load="{{ $course->course_load }}"
                                        data-field-pre_requisite="{{ $course->pre_requisite }}"
                                        data-field-sc_code="{{ $course->mqfSc?->code }}"
                                        data-field-vbe_ept_code="{{ $course->vbe?->code }}"
                                        data-field-sdg_code="{{ $course->sdg?->code }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('curriculum.courses.destroy', $course) }}" data-confirm="Delete course {{ $course->course_code }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-8 text-center text-sm text-slate-400">No courses found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $courses->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-course" title="New course" size="lg" subtitle="Course code, titles, credit hours and curriculum mapping">
        <form method="POST" action="{{ route('curriculum.courses.store') }}" class="space-y-4">
            @csrf
            @include('curriculum.partials.course-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create course</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-course" title="Edit course" size="lg">
        <form method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            @include('curriculum.partials.course-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-courses" title="Bulk import courses"
        subtitle="CSV columns: course_code, course_name_en, course_name_my, credit_hours, course_type_code, course_load, pre_requisite, sc_code, vbe_ept_code, sdg_code">
        <form method="POST" action="{{ route('curriculum.courses.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono break-words">course_code,course_name_en,course_name_my,credit_hours,course_type_code,course_load,pre_requisite,sc_code,vbe_ept_code,sdg_code</p>
                <p class="mt-1"><span class="font-semibold">course_type_code</span>, <span class="font-semibold">sc_code</span>, <span class="font-semibold">vbe_ept_code</span> and <span class="font-semibold">sdg_code</span> are matched against the codes in Manage Configs. An unknown code fails that row.</p>
                <p class="mt-1">Existing course codes are updated, new codes are created. Rows that fail are skipped and listed in the summary.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
