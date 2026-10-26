@extends('layouts.app')

@section('title', 'Study Plan Details')

@section('content')
    <x-page-header title="Study Plan Details"
        subtitle="The courses of each study plan, placed by curriculum semester">
        <x-slot:actions>
            <button type="button" data-modal-open="import-details" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-detail" class="btn-primary">
                <x-icon name="plus" class="size-4" /> Add course row
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    <form method="GET" data-scroll-keep class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search plan code, course code or title…" class="input pl-9">
        </div>
        <select name="plan" data-auto-submit class="select">
            <option value="">All study plans</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->code }}" @selected(request('plan') === $plan->code)>{{ $plan->code }} — {{ $plan->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('curriculum.study-plan-details.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center">SP ID</th>
                        <th>Study plan</th>
                        <th>Course</th>
                        <th class="text-center">Semester</th>
                        <th>Type</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($details as $detail)
                        <tr>
                            <td class="text-center text-xs text-slate-400">#{{ $detail->id }}</td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $detail->plan?->code ?? '—' }}</p>
                                <p class="text-xs text-slate-400">{{ $detail->plan?->program?->code }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $detail->course?->code ?? 'Row without a course' }}</p>
                                <p class="text-xs text-slate-500">{{ $detail->course?->name ?? $detail->placeholder_key }}</p>
                            </td>
                            <td class="text-center">
                                @if ($detail->curriculumSemester())
                                    <span class="font-semibold text-slate-700">{{ $detail->curriculumSemester() }}</span>
                                    @if ($detail->isSpecialSemester())
                                        <x-badge tone="amber">special</x-badge>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($detail->course?->courseType)
                                    <x-badge tone="slate">{{ $detail->course->courseType->code }}</x-badge>
                                @else
                                    <span class="text-xs text-slate-400">{{ $detail->category ?: '—' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-detail"
                                        data-action="{{ route('curriculum.study-plan-details.update', $detail) }}"
                                        data-title="Edit study plan row #{{ $detail->id }}"
                                        data-field-study_plan_code="{{ $detail->plan?->code }}"
                                        data-field-course_code="{{ $detail->course?->code }}"
                                        data-field-semester="{{ $detail->curriculumSemester() }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('curriculum.study-plan-details.destroy', $detail) }}" data-confirm="Remove {{ $detail->course?->code ?? 'this row' }} from the study plan?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-sm text-slate-400">No study plan rows found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $details->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-detail" title="Add course to a study plan" subtitle="Pick the plan, the course and its curriculum semester">
        <form method="POST" action="{{ route('curriculum.study-plan-details.store') }}" class="space-y-4">
            @csrf
            @include('curriculum.partials.study-plan-detail-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Add row</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-detail" title="Edit study plan row">
        <form method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            @include('curriculum.partials.study-plan-detail-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-details" title="Bulk import study plan details"
        subtitle="CSV columns: study_plan_code, course_code, semester">
        <form method="POST" action="{{ route('curriculum.study-plan-details.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono">study_plan_code,course_code,semester</p>
                <p class="mt-1"><span class="font-semibold">study_plan_code</span> and <span class="font-semibold">course_code</span> must already exist. A course already in the plan has its semester updated.</p>
                <p class="mt-1">Rows that fail are skipped and listed in the summary; the rest of the file still imports.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
