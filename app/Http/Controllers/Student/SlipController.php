<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurriculumService;
use Illuminate\View\View;

class SlipController extends Controller
{
    public function show(CurriculumService $curriculum): View
    {
        /** @var User $student */
        $student = auth()->user();

        $session = Setting::get('active_session', '2025/2026');
        $semester = Setting::get('active_semester', '1');

        $registrations = $student->preRegistrations()
            ->with(['offering.course', 'offering.lecturer'])
            ->where('session', $session)
            ->where('semester', $semester)
            ->get()
            ->sortBy(fn ($r) => $r->offering->course->code);

        return view('student.slip', [
            'student' => $student->load('program', 'plan', 'advisor', 'department'),
            'registrations' => $registrations,
            'totalCredits' => $registrations->whereIn('status', ['pending', 'approved'])->sum('credits'),
            'submission' => RegistrationSubmission::forTerm($student, $session, $semester),
            'activeSession' => $session,
            'activeSemester' => $semester,
            'generatedAt' => now(),
        ]);
    }
}
