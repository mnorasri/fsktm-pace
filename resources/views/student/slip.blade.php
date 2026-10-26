@extends('layouts.app')

@section('title', 'Pre-Registration Slip')

@section('content')
    <div class="no-print mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('student.pre-registration') }}" class="btn-ghost">
            <x-icon name="arrow-path" class="size-4" /> Back to pre-registration
        </a>
        <button type="button" onclick="window.print()" class="btn-primary">
            <x-icon name="printer" class="size-4" /> Print / Save as PDF
        </button>
    </div>

    <div class="print-container card mx-auto max-w-3xl p-8">
        {{-- Letterhead --}}
        <div class="border-b-2 border-slate-800 pb-4">
            <div class="flex flex-col items-center gap-2 text-center">
                <img src="{{ asset('images/uthm-logo.png') }}" alt="Universiti Tun Hussein Onn Malaysia"
                    class="h-20 w-auto object-contain" />
                <div>
                    <p class="text-lg font-bold leading-tight text-slate-900">Faculty of Computer Science &amp; Information Technology</p>
                    <p class="text-xl leading-tight text-slate-500">Universiti Tun Hussein Onn Malaysia</p>
                </div>
            </div>
        </div>

        <h1 class="mt-5 text-center text-xl font-bold uppercase tracking-wide text-slate-900">
            Course Pre-Registration Slip
        </h1>
        <p class="text-center text-sm font-semibold text-slate-700">
            {{ $activeSession }} · Semester {{ $activeSemester }}
        </p>
        <p class="text-center text-xs text-slate-400">
            Generated on {{ $generatedAt->format('d F Y, h:i A') }} · Slip No. PR-{{ str_pad((string) $student->id, 5, '0', STR_PAD_LEFT) }}-{{ $activeSession }}
        </p>

        @php
            $statusClasses = match ($submission?->status) {
                'approved' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
                'returned' => 'border-rose-300 bg-rose-50 text-rose-800',
                'submitted' => 'border-amber-300 bg-amber-50 text-amber-800',
                default => 'border-slate-300 bg-slate-50 text-slate-600',
            };
            $statusText = match ($submission?->status) {
                'approved' => 'Approved by '.($submission->reviewer?->name ?? 'the academic advisor').' on '.$submission->reviewed_at?->format('d M Y'),
                'returned' => 'Returned by the academic advisor on '.$submission->reviewed_at?->format('d M Y').' — awaiting revision',
                'submitted' => 'Submitted '.$submission->submitted_at?->format('d M Y, h:i A').' — awaiting advisor approval',
                default => 'Not yet submitted to the academic advisor',
            };
        @endphp

        <div class="mt-4 flex justify-center">
            <span class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold {{ $statusClasses }}">
                <span class="size-1.5 rounded-full bg-current"></span>
                {{ $statusText }}
            </span>
        </div>

        {{-- Student details --}}
        <dl class="mt-6 grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Name</dt>
                <dd class="font-semibold text-slate-800">{{ $student->name }}</dd>
            </div>
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Student ID</dt>
                <dd class="font-mono font-semibold text-slate-800">{{ $student->student_id }}</dd>
            </div>
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Programme</dt>
                <dd class="font-semibold text-slate-800">{{ $student->program?->code }}</dd>
            </div>
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Department</dt>
                <dd class="font-semibold text-slate-800">{{ $student->department?->code }}</dd>
            </div>
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Curriculum Plan</dt>
                <dd class="font-semibold text-slate-800">{{ $student->plan?->code }}</dd>
            </div>
            <div class="flex justify-between border-b border-dashed border-slate-200 pb-2">
                <dt class="text-slate-500">Academic Advisor (PAK)</dt>
                <dd class="font-semibold text-slate-800">{{ $student->advisor?->name ?? '—' }}</dd>
            </div>
        </dl>

        {{-- Course table --}}
        <table class="mt-6 w-full border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="border border-slate-200 px-3 py-2">#</th>
                    <th class="border border-slate-200 px-3 py-2">Course Code</th>
                    <th class="border border-slate-200 px-3 py-2">Course Title</th>
                    <th class="border border-slate-200 px-3 py-2 text-center">Section</th>
                    <th class="border border-slate-200 px-3 py-2 text-center">Credits</th>
                    <th class="border border-slate-200 px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registrations as $registration)
                    <tr>
                        <td class="border border-slate-200 px-3 py-2">{{ $loop->iteration }}</td>
                        <td class="border border-slate-200 px-3 py-2 font-mono font-semibold">{{ $registration->offering->course->code }}</td>
                        <td class="border border-slate-200 px-3 py-2">{{ $registration->offering->course->name }}</td>
                        <td class="border border-slate-200 px-3 py-2 text-center">{{ $registration->offering->section }}</td>
                        <td class="border border-slate-200 px-3 py-2 text-center">{{ $registration->credits }}</td>
                        <td class="border border-slate-200 px-3 py-2 text-center capitalize">{{ $registration->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="border border-slate-200 px-3 py-6 text-center text-slate-400">
                            No courses registered for this term.
                        </td>
                    </tr>
                @endforelse
                <tr class="bg-slate-50 font-semibold">
                    <td colspan="4" class="border border-slate-200 px-3 py-2 text-right">Total Credits</td>
                    <td class="border border-slate-200 px-3 py-2 text-center">{{ $totalCredits }}</td>
                    <td class="border border-slate-200 px-3 py-2"></td>
                </tr>
            </tbody>
        </table>

        <p class="mt-3 text-xs text-slate-500">
            Maximum load per term: <span class="font-semibold">20 credits</span>.
            Committed load: <span class="font-semibold">{{ $totalCredits }} credits</span>.
        </p>

        {{-- Signatures --}}
        <div class="mt-12 grid grid-cols-3 gap-6 text-center text-xs">
            @foreach (['Student', 'Academic Advisor (PAK)', 'Head of Department'] as $label)
                <div class="print-break">
                    <div class="mb-1 border-t border-slate-400 pt-2"></div>
                    <p class="font-semibold text-slate-700">{{ $label }}</p>
                    <p class="text-slate-400">Name &amp; Date</p>
                </div>
            @endforeach
        </div>

        <p class="mt-10 border-t border-slate-200 pt-3 text-center text-[10px] text-slate-400">
            This is a computer-generated slip from the FSKTM PACE System. Valid only with authorised signatures.
        </p>
    </div>
@endsection
