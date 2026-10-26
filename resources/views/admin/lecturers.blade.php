@extends('layouts.app')

@section('title', 'Manage Lecturers')

@section('content')
    <x-page-header title="Manage Lecturers"
        subtitle="Lecturer and academic advisor accounts">
        <x-slot:actions>
            <button type="button" data-modal-open="import-lecturers" class="btn-ghost">
                <x-icon name="upload" class="size-4" /> Import CSV
            </button>
            <button type="button" data-modal-open="add-lecturer" class="btn-primary">
                <x-icon name="plus" class="size-4" /> New lecturer
            </button>
        </x-slot:actions>
    </x-page-header>

    @include('partials.import-report')

    <form method="GET" class="card card-pad mb-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="relative sm:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search staff ID, name or email…" class="input pl-9">
        </div>
        <select name="department_id" data-auto-submit class="select">
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->code }}</option>
            @endforeach
        </select>
    </form>

    <x-card pad>
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>Lecturer</th>
                        <th>Department</th>
                        <th class="text-center">Advisees</th>
                        <th>Role</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturers as $lecturer)
                        <tr>
                            <td class="font-mono text-xs font-semibold text-slate-600">{{ $lecturer->staff_id ?? '—' }}</td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $lecturer->name }}</p>
                                <p class="text-xs text-slate-400">{{ $lecturer->email }}</p>
                            </td>
                            <td class="text-xs text-slate-500">{{ $lecturer->department?->code ?? '—' }}</td>
                            <td class="text-center font-semibold">{{ $lecturer->advisees_count }}</td>
                            <td>
                                <x-badge :tone="$lecturer->is_hod ? 'amber' : 'emerald'">{{ $lecturer->is_hod ? 'HoD' : 'Advisor' }}</x-badge>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" class="btn-ghost btn-sm"
                                        data-edit="edit-lecturer"
                                        data-action="{{ route('admin.lecturers.update', $lecturer) }}"
                                        data-title="Edit {{ $lecturer->name }}"
                                        data-field-name="{{ $lecturer->name }}"
                                        data-field-email="{{ $lecturer->email }}"
                                        data-field-staff_id="{{ $lecturer->staff_id }}"
                                        data-field-department_id="{{ $lecturer->department_id }}"
                                        data-field-status="{{ $lecturer->status }}">
                                        <x-icon name="cog" class="size-3.5" /> Edit
                                    </button>
                                    <form method="POST" action="{{ route('admin.lecturers.reset-password', $lecturer) }}" data-confirm="Reset password for {{ $lecturer->name }}?">
                                        @csrf
                                        <button class="btn-subtle btn-sm"><x-icon name="lock" class="size-3.5" /> Reset</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.lecturers.destroy', $lecturer) }}" data-confirm="Remove {{ $lecturer->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm"><x-icon name="x" class="size-3.5" /></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-sm text-slate-400">No lecturers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $lecturers->links() }}</div>
    </x-card>

    {{-- Add --}}
    <x-modal id="add-lecturer" title="New lecturer"
        subtitle="Staff ID, Lecturer Name, Lecturer Email, Department, Password">
        <form method="POST" action="{{ route('admin.lecturers.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            <label class="block">
                <span class="label">Staff ID <span class="text-[10px] font-bold uppercase tracking-wider text-brand-600">Primary key</span></span>
                <input type="text" name="staff_id" class="input font-mono" placeholder="e.g. LEC010" required>
            </label>
            <label class="block"><span class="label">Lecturer Name</span><input type="text" name="name" class="input" required></label>
            <label class="block"><span class="label">Lecturer Email</span><input type="email" name="email" class="input" required></label>
            <label class="block">
                <span class="label">Department</span>
                <select name="department_id" class="select">
                    <option value="">— None —</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block"><span class="label">Password</span><input type="text" name="password" class="input" placeholder="Defaults to: R@hsi4"></label>
            <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="plus" class="size-4" /> Create</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit --}}
    <x-modal id="edit-lecturer" title="Edit lecturer">
        <form method="POST" action="" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <label class="block">
                <span class="label">Staff ID <span class="text-[10px] font-bold uppercase tracking-wider text-brand-600">Primary key</span></span>
                <input type="text" name="staff_id" class="input font-mono" required>
            </label>
            <label class="block"><span class="label">Lecturer Name</span><input type="text" name="name" class="input" required></label>
            <label class="block"><span class="label">Lecturer Email</span><input type="email" name="email" class="input" required></label>
            <label class="block">
                <span class="label">Department</span>
                <select name="department_id" class="select">
                    <option value="">— None —</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block"><span class="label">New password (optional)</span><input type="text" name="password" class="input" placeholder="Leave blank to keep current"></label>
            <label class="block sm:col-span-2">
                <span class="label">Status</span>
                <select name="status" class="select">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>
            <div class="sm:col-span-2 flex items-center justify-end gap-2 pt-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="check" class="size-4" /> Save changes</button>
            </div>
        </form>
    </x-modal>

    {{-- Import --}}
    <x-modal id="import-lecturers" title="Bulk import lecturers"
        subtitle="CSV columns: staff_id, lecturer_name, lecturer_email, department_code, password">
        <form method="POST" action="{{ route('admin.lecturers.import') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv" class="input" required>
            <div class="rounded-2xl bg-slate-50 p-3.5 text-xs text-slate-500">
                <p class="font-mono">staff_id,lecturer_name,lecturer_email,department_code,password</p>
                <p class="mt-1">Field 1 is the <span class="font-semibold">staff ID</span> (the lecturer's primary key); field 4 is the <span class="font-semibold">department code</span> (e.g. <span class="font-mono">JKP</span>).</p>
                <p class="mt-2 font-mono">LEC010,Dr. Siti Aminah,siti@fsktm.test,JKP,secret123</p>
                <p class="mt-1">Rows are matched by staff ID (or email) and updated. A blank password keeps the current one; new accounts default to <span class="font-semibold">R@hsi4</span>.</p>
                <p class="mt-1">A row that fails (missing staff ID, unknown department code, clashing email) is skipped; the rest of the file still imports and the summary lists every skipped row.</p>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" data-modal-close class="btn-ghost">Cancel</button>
                <button type="submit" class="btn-primary"><x-icon name="upload" class="size-4" /> Import</button>
            </div>
        </form>
    </x-modal>
@endsection
