<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurriculumService;
use App\Services\StudyPlanService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly StudyPlanService $plans,
    ) {}

    public function index(): View
    {
        /** @var User $advisor */
        $advisor = auth()->user();

        // Heads of Department supervise every student in their department;
        // regular PAK advisors see only their assigned advisees.
        $session = Setting::get('active_session');
        $semester = Setting::get('active_semester');

        $advisees = ($advisor->isHod() && $advisor->department_id
                ? User::where('role', User::ROLE_STUDENT)->where('department_id', $advisor->department_id)
                : $advisor->advisees())
            ->with([
                'program',
                'plan.program',
                'advisor',
                'studyPlanEntries.course.courseType',
                'studyPlanEntries.electiveSlot',
                'preRegistrations' => fn ($q) => $q->latest(),
                'preRegistrations.offering.course',
                // The pre-registration batch the student sent for this term.
                'registrationSubmissions' => fn ($q) => $q->where('session', $session)->where('semester', $semester),
            ])
            ->orderBy('name')
            ->get();

        $summary = [
            'advisees' => $advisees->count(),
            'pending_plans' => $advisees->where('plan_status', 'submitted')->count(),
            // Students whose pre-registration batch is waiting for a decision.
            'pending_registrations' => $advisees->filter(
                fn (User $student) => $student->registrationSubmissions->first()?->status === 'submitted'
            )->count(),
        ];

        $students = $advisees->map(function (User $student) use ($session, $semester) {
            // The same per-year odd / even grid the student sees on their own
            // Study Plan page, so a plan can be reviewed as it was built.
            $grid = $this->plans->grid($student, $student->studyPlanEntries);

            // The batch awaiting a decision, and the term's registrations laid
            // out exactly like the slip the student prints.
            $submission = $student->registrationSubmissions->first();
            $termRequests = $student->preRegistrations
                ->where('session', $session)
                ->where('semester', $semester)
                ->sortBy(fn (PreRegistration $r) => $r->offering->course->code)
                ->values();
            $counted = $termRequests->whereIn('status', ['pending', 'approved']);
            $otherRequests = $student->preRegistrations
                ->reject(fn (PreRegistration $r) => $r->session == $session && $r->semester == $semester)
                ->values();

            // Prerequisite placement warnings, mirroring the student's flags.
            $warnings = [];

            foreach ($student->studyPlanEntries as $entry) {
                if (! $entry->course?->prerequisites) {
                    continue;
                }

                $result = $this->curriculum->checkPrerequisites($student, $entry->course, $entry, false);

                if (! empty($result['late'])) {
                    $warnings[$entry->id] = $result['late'];
                }
            }

            return [
                'student' => $student,
                'credits' => $student->creditSummary(),
                'years' => $grid['years'],
                'semesters' => $grid['semesters'],
                'has_plan' => $student->studyPlanEntries->isNotEmpty(),
                'warnings' => $warnings,
                'registrations' => $student->preRegistrations,
                'term_registrations' => $termRequests,
                'other_registrations' => $otherRequests,
                'pending_registrations' => $student->preRegistrations->where('status', 'pending')->count(),
                'submission' => $submission,
                'registration_totals' => [
                    'courses' => $counted->count(),
                    'credits' => (int) $counted->sum('credits'),
                ],
                'active' => $submission?->status === 'submitted',
            ];
        });

        return view('advisor.dashboard', [
            'advisor' => $advisor,
            'summary' => $summary,
            'students' => $students,
            'semesterLabels' => StudyPlanService::SEMESTER_LABELS,
            'activeSession' => Setting::get('active_session'),
            'activeSemester' => Setting::get('active_semester'),
        ]);
    }
}
