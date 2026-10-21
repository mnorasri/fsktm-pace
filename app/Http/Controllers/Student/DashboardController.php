<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Offering;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurriculumService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(CurriculumService $curriculum): View
    {
        /** @var User $student */
        $student = auth()->user();

        $session = Setting::get('active_session', '2025/2026');
        $semester = Setting::get('active_semester', '1');

        $entries = $student->studyPlanEntries()->with('course')->get();

        $warnings = $entries
            ->filter(fn ($entry) => $entry->course !== null)
            ->mapWithKeys(function ($entry) use ($student, $curriculum) {
                $result = $curriculum->checkPrerequisites($student, $entry->course, $entry, false);

                return [$entry->id => $result['late']];
            })
            ->filter(fn ($late) => ! empty($late));

        $registrations = $student->preRegistrations()
            ->with('offering.course')
            ->where('session', $session)
            ->where('semester', $semester)
            ->get();

        $nextTerm = $entries
            ->filter(fn ($entry) => $entry->status === 'PLANNED' && $entry->course !== null)
            ->sortBy(fn ($entry) => $entry->orderKey())
            ->groupBy(fn ($entry) => $entry->semesterKey())
            ->first() ?? collect();

        return view('student.dashboard', [
            'student' => $student,
            'credits' => $student->creditSummary(),
            'entries' => $entries,
            'warnings' => $warnings,
            'registrations' => $registrations,
            'submission' => RegistrationSubmission::forTerm($student, $session, $semester),
            'committedCredits' => $curriculum->committedCredits($student, $session, $semester),
            'creditCap' => CurriculumService::MAX_CREDITS_PER_TERM,
            'upcoming' => $nextTerm,
            'activeSession' => $session,
            'activeSemester' => $semester,
            'availableOfferings' => Offering::where('session', $session)->where('semester', $semester)->where('is_offered', true)->count(),
        ]);
    }
}
