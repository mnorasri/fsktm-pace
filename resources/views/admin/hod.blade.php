@extends('layouts.app')

@section('title', 'Manage HoD')

@section('content')
    <x-page-header title="Manage Head of Department"
        subtitle="Assign HoD privileges to existing lecturers based on their department" />

    {{-- Department summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($departments as $department)
            <x-stat-card :label="$department->code.' — '.$department->name" :value="$department->hod_count"
                icon="identification" :tone="$department->hod_count > 0 ? 'emerald' : 'slate'"
                :hint="$department->hod_count > 0 ? 'HoD assigned' : 'No HoD assigned'" />
        @endforeach
    </div>

    <form method="GET" class="card card-pad mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="relative sm:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, email or staff ID…" class="input pl-9">
        </div>
        <select name="department_id" data-auto-submit class="select">
            <option value="">All departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->code }} — {{ $department->name }}</option>
            @endforeach
        </select>
    </form>

    <x-card class="mt-6" pad title="Lecturer Directory" subtitle="Toggle HoD privileges per lecturer">
        <div class="-mx-6 overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Lecturer</th>
                        <th>Department</th>
                        <th>Current Role</th>
                        <th class="text-right">Assign HoD</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($advisors as $advisor)
                        <tr>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $advisor->name }}</p>
                                <p class="text-xs text-slate-400">{{ $advisor->staff_id }} · {{ $advisor->email }}</p>
                            </td>
                            <td class="text-xs text-slate-500">{{ $advisor->department?->code ?? '—' }}</td>
                            <td>
                                <x-badge :tone="$advisor->is_hod ? 'amber' : 'slate'" dot>{{ $advisor->is_hod ? 'Head of Department' : 'Advisor' }}</x-badge>
                            </td>
                            <td>
                                <div class="flex justify-end">
                                    <form method="POST" action="{{ route('admin.hod.toggle', $advisor) }}">
                                        @csrf
                                        <button class="{{ $advisor->is_hod ? 'btn-ghost' : 'btn-primary' }} btn-sm">
                                            <x-icon :name="$advisor->is_hod ? 'x' : 'check'" class="size-3.5" />
                                            {{ $advisor->is_hod ? 'Revoke HoD' : 'Make HoD' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">No lecturers available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
