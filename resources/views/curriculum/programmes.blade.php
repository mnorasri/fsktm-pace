@extends('layouts.app')

@section('title', 'Programmes')

@section('content')
    <x-page-header title="Programmes"
        subtitle="Programme codes, names and the department each programme belongs to">
        <x-slot:actions>
            <button type="button" data-modal-open="import-programmes" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-programme" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New programme
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    <form method="GET" data-scroll-keep class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative lg:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search programme code or name…" class="input pl-9">
        </div>
        <select name="department_id" data-auto-submit class="select">
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->code }} — {{ $department->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn-primary flex-1">Filter</button>
            <a href="{{ route('curriculum.programmes.index') }}" class="btn-ghost">Reset</a>
        </div>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Programme</th>
                        <th>Department</th>
                        <th class="text-center">Study plans</th>
                        <th class="text-center">Courses</th>
                        <th class="text-center">Duration</th>
                        <th class="text-center">Credits</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programs as $program)
                        <tr>
                            <td class="font-semibold text-slate-800">{{ $program->code }}</td>
                            <td class="text-slate-700">{{ $program->name }}</td>
                            <td>
                                @if ($program->department)
                                    <x-badge tone="slate">{{ $program->department->code }}</x-badge>
                                    <span class="ml-1 text-xs text-slate-400">{{ $program->department->name }}</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-center text-sm">{{ $program->plans_count }}</td>
                            <td class="text-center text-sm">{{ $program->courses_count }}</td>
                            <td class="text-center text-xs text-slate-500">{{ rtrim(rtrim(number_format((float) $program->duration_years, 1), '0'), '.') }} yrs</td>
                            <td class="text-center text-xs text-slate-500">{{ $program->total_credits }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-programme"
                                        data-action="{{ route('curriculum.programmes.update', $program) }}"
                                        data-title="Edit {{ $program->code }}"
                                        data-field-programme_code="{{ $program->code }}"
                                        data-field-programme_name="{{ $program->name }}"
                                        data-field-department_code="{{ $program->department?->code }}"
                                        data-field-duration_years="{{ $program->duration_years }}"
                                        data-field-total_credits="{{ $program->total_credits }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('curriculum.programmes.destroy', $program) }}" data-confirm="Delete programme {{ $program->code }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-sm text-slate-400">No programmes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $programs->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-programme" title="New programme" subtitle="Programme code, name and owning department">
        <form method="POST" action="{{ route('curriculum.programmes.store') }}" class="space-y-4">
            @csrf
            @include('curriculum.partials.programme-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create programme</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-programme" title="Edit programme">
        <form method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            @include('curriculum.partials.programme-fields')
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-programmes" title="Bulk import programmes"
        subtitle="CSV columns: programme_code, programme_name, department_code">
        <form method="POST" action="{{ route('curriculum.programmes.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <label class="block">
                <span class="label">CSV file</span>
                <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            </label>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-semibold text-slate-600">Expected header</p>
                <p class="mt-1 font-mono">programme_code,programme_name,department_code</p>
                <p class="mt-1"><span class="font-semibold">department_code</span> must already exist in Manage Configs.</p>
                <p class="mt-1">Optional extra columns: <span class="font-mono">duration_years</span>, <span class="font-mono">total_credits</span>.</p>
                <p class="mt-1">Existing programme codes are updated, new codes are created. Rows that fail are skipped and listed in the summary.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
