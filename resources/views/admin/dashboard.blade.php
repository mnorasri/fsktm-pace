@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <x-page-header title="Administration Overview"
        subtitle="{{ \App\Models\Setting::get('active_session', '2025/2026') }} · Semester {{ \App\Models\Setting::get('active_semester', '1') }}">
        <x-slot:actions>
            <a href="{{ route('admin.configs.index') }}" class="btn-ghost"><x-icon name="cog" class="size-4" /> Configs</a>
            <a href="{{ route('admin.courses.index') }}" class="btn-primary"><x-icon name="book" class="size-4" /> Courses</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Students" :value="$stats['students']" icon="users" tone="brand" />
        <x-stat-card label="Lecturers" :value="$stats['lecturers']" icon="briefcase" tone="emerald" />
        <x-stat-card label="Heads of Dept." :value="$stats['hods']" icon="identification" tone="amber" />
        <x-stat-card label="Courses" :value="$stats['courses']" icon="book" tone="sky" />
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Departments" :value="$stats['departments']" icon="building" tone="slate" />
        <x-stat-card label="Programmes" :value="$stats['programs']" icon="academic-cap" tone="brand" />
        <x-stat-card label="Course Plans" :value="$stats['plans']" icon="layers" tone="sky" />
        <x-stat-card label="Offerings (active)" :value="$stats['offerings']" icon="clipboard" tone="emerald" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2" pad title="Distribution by Department" subtitle="Courses, students and lecturers per department" pad>
            <div class="-mx-6 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="text-center">Courses</th>
                            <th class="text-center">Students</th>
                            <th class="text-center">Lecturers</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($byDepartment as $department)
                            <tr>
                                <td>
                                    <p class="font-semibold text-slate-800">{{ $department->code }}</p>
                                    <p class="text-xs text-slate-400">{{ $department->name }}</p>
                                </td>
                                <td class="text-center font-semibold">{{ $department->courses_count }}</td>
                                <td class="text-center font-semibold">{{ $department->students_count }}</td>
                                <td class="text-center font-semibold">{{ $department->lecturers_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card pad title="Quick Actions">
            <div class="space-y-2.5">
                @foreach ([
                    ['admin.configs.index', 'cog', 'Manage Configs', 'Departments, programmes, plans'],
                    ['admin.courses.index', 'book', 'Manage Courses', 'Create, filter and bulk import'],
                    ['admin.lecturers.index', 'briefcase', 'Manage Lecturers', 'Accounts and PAK mapping'],
                    ['admin.students.index', 'users', 'Manage Students', 'Directory and CSV import'],
                    ['admin.hod.index', 'identification', 'Manage HoD', 'Assign department heads'],
                    ['admin.head-of-program.index', 'academic-cap', 'Manage Head of Program', 'Assign a head per programme'],
                ] as [$route, $icon, $label, $desc])
                    <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3.5 transition hover:border-brand-300 hover:bg-brand-50/50">
                        <span class="grid size-9 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            <x-icon :name="$icon" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-800">{{ $label }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $desc }}</span>
                        </span>
                        <x-icon name="chevron-down" class="size-4 -rotate-90 text-slate-300" />
                    </a>
                @endforeach
            </div>
        </x-card>
    </div>
@endsection
