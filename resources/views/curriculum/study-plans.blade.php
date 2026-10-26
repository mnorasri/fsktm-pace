@extends('layouts.app')

@section('title', 'Study Plans')

@section('content')
    <x-page-header title="Study Plans"
        subtitle="Each plan belongs to a programme and holds its courses, year by year, on the visual grid">
        <x-slot:actions>
            <button type="button" data-modal-open="import-plans" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-plan" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New study plan
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    <form method="GET" data-scroll-keep class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search plan code or name…" class="input pl-9">
        </div>
        <select name="program_id" data-auto-submit class="select">
            <option value="">All programmes</option>
            @foreach ($programs as $program)
                <option value="{{ $program->id }}" @selected(request('program_id') == $program->id)>{{ $program->code }} — {{ $program->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('curriculum.study-plans.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Study plan</th>
                        <th>Programme</th>
                        <th class="text-center">Version</th>
                        <th class="text-center">Semesters</th>
                        <th class="text-center">Course rows</th>
                        <th class="text-center">Students</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $plan->code }}</p>
                                <p class="text-xs text-slate-500">{{ $plan->name }}</p>
                            </td>
                            <td>
                                <span class="font-semibold text-slate-700">{{ $plan->program?->code ?? '—' }}</span>
                                <p class="text-xs text-slate-400">{{ $plan->program?->name }}</p>
                            </td>
                            <td class="text-center text-xs text-slate-500">{{ $plan->version ?: '—' }}</td>
                            <td class="text-center text-sm">{{ $plan->total_semester ?? '—' }}</td>
                            <td class="text-center text-sm">{{ $plan->plan_courses_count }}</td>
                            <td class="text-center text-sm">{{ $plan->students_count }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    @if (auth()->user()->isAdmin() || auth()->user()->isHod())
                                        <a href="{{ route('advisor.plans.index', ['plan_id' => $plan->id]) }}" class="btn-ghost btn-sm" title="View the visual year grid">
                                            <x-icon name="calendar" class="size-3.5" /> Grid
                                        </a>
                                    @endif
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-plan"
                                        data-action="{{ route('curriculum.study-plans.update', $plan) }}"
                                        data-title="Edit {{ $plan->code }}"
                                        data-field-study_plan_code="{{ $plan->code }}"
                                        data-field-study_plan_name="{{ $plan->name }}"
                                        data-field-programme_code="{{ $plan->program?->code }}"
                                        data-field-version="{{ $plan->version }}"
                                        data-field-total_semester="{{ $plan->total_semester }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('curriculum.study-plans.destroy', $plan) }}" data-confirm="Delete study plan {{ $plan->code }} and all of its course rows?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-sm text-slate-400">No study plans found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $plans->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-plan" title="New study plan" subtitle="Plan code, programme, version and number of semesters">
        <form method="POST" action="{{ route('curriculum.study-plans.store') }}" class="space-y-4">
            @csrf
            @include('curriculum.partials.study-plan-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create study plan</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-plan" title="Edit study plan">
        <form method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            @include('curriculum.partials.study-plan-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-plans" title="Bulk import study plans"
        subtitle="CSV columns: study_plan_code, study_plan_name, programme_code, version, total_semester">
        <form method="POST" action="{{ route('curriculum.study-plans.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono">study_plan_code,study_plan_name,programme_code,version,total_semester</p>
                <p class="mt-1"><span class="font-semibold">programme_code</span> must already exist. <span class="font-semibold">version</span> and <span class="font-semibold">total_semester</span> are optional.</p>
                <p class="mt-1">Existing plan codes are updated, new codes are created. Rows that fail are skipped and listed in the summary.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
