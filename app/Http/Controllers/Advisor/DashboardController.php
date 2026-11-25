<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\PreRegistration;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurriculumService;
use App\Services\StudyPlanService;
use App\Support\StudyPlanRows;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Curriculum grids keyed by plan id: advisees usually share a plan, so a
     * page renders each plan once even when it holds many students.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $curriculumCache = [];

    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly StudyPlanService $plans,
        private readonly StudyPlanRows $planRows,
    ) {}

    public function index(): View
    {
        /** @var User $advisor */
        $advisor = auth()->user();

        // Every advisor — a Head of Department included — sees only the
        // students whose advisor_id is their own staff ID.
        $session = Setting::get('active_session');
        $semester = Setting::get('active_semester');

        $advisees = $this->adviseeQuery($advisor, $session, $semester)
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
            return $this->buildRow($student, $session, $semester);
        });

        $selectedId = request()->string('student')->value();
        $selected = $students->first(fn (array $row) => $row['student']->id === $selectedId);

        // Default to the first advisee so the panel is never empty on arrival.
        $selected ??= $students->first();

        return view('advisor.dashboard', $this->payload(
            $advisor, $summary, $students, $selected, $this->activeTab(), $session, $semester
        ));
    }

    /**
     * The detail panel for a single supervisee, served as an HTML fragment so
     * the supervisee list can swap it in without reloading the page.
     */
    public function detail(): View
    {
        /** @var User $advisor */
        $advisor = auth()->user();

        $session = Setting::get('active_session');
        $semester = Setting::get('active_semester');
        $studentId = request()->string('student')->value();

        // Scoped to the advisor's own advisees, so a stranger's id 404s.
        $student = $this->adviseeQuery($advisor, $session, $semester)->find($studentId);

        abort_unless($student, 404);

        $row = $this->buildRow($student, $session, $semester);

        return view('advisor.partials.detail', $this->payload(
            $advisor, [], collect([$row]), $row, $this->activeTab(), $session, $semester
        ));
    }

    /**
     * The advisees query with every relation the panels need, eager loaded.
     * The same shape feeds both the full page and the AJAX detail fragment.
     */
    private function adviseeQuery(User $advisor, ?string $session, ?string $semester)
    {
        return $advisor->advisees()->with([
            'department',
            'program',
            'plan.program',
            'advisor',
            'studyPlanEntries.course.courseType',
            'studyPlanEntries.electiveSlot',
            'preRegistrations' => fn ($q) => $q->latest(),
            'preRegistrations.offering.course',
            // The pre-registration batch the student sent for this term.
            'registrationSubmissions' => fn ($q) => $q->where('session', $session)->where('semester', $semester),
        ]);
    }

    private function activeTab(): string
    {
        return request()->query('tab') === 'plan' ? 'plan' : 'registrations';
    }

    /**
     * The rendered shape of one advisee on the dashboard.
     *
     * @return array<string, mixed>
     */
    private function buildRow(User $student, ?string $session, ?string $semester): array
    {
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
            // When the student has not built their own plan yet, the advisor
            // still reviews the curriculum their programme assigned to them.
            'curriculum' => $student->studyPlanEntries->isEmpty() ? $this->curriculumFor($student) : null,
            'warnings' => $warnings,
            'registrations' => $student->preRegistrations,
            'term_registrations' => $termRequests,
            'registration_prerequisites' => $termRequests->mapWithKeys(fn (PreRegistration $registration) => [
                $registration->id => $this->curriculum->prerequisiteStanding($student, $registration->offering->course),
            ]),
            'other_registrations' => $otherRequests,
            'pending_registrations' => $student->preRegistrations->where('status', 'pending')->count(),
            'submission' => $submission,
            'registration_totals' => [
                'courses' => $counted->count(),
                'credits' => (int) $counted->sum('credits'),
            ],
            'active' => $submission?->status === 'submitted',
        ];
    }

    /**
     * The curriculum of the study plan assigned to a student, laid out year by
     * year. Null when the student has no plan assigned.
     *
     * @return array<string, mixed>|null
     */
    private function curriculumFor(User $student): ?array
    {
        $plan = $student->plan;

        if (! $plan) {
            return null;
        }

        if (! isset($this->curriculumCache[$plan->id])) {
            $rows = $this->planRows->for($plan);
            $placed = $rows->where('placed', true);

            $this->curriculumCache[$plan->id] = [
                'plan' => $plan,
                'grid' => $this->planRows->grid(
                    $rows,
                    (float) ($plan->program?->duration_years ?? 4),
                ),
                'unplaced' => $rows->where('placed', false)->values(),
                'courses' => $rows->count(),
                'credits' => (int) $placed->sum('credits'),
            ];
        }

        return $this->curriculumCache[$plan->id];
    }

    /**
     * The supervisees split by the study plan assigned to them, so the list can
     * show one section per plan. Students with no plan fall into a last group.
     *
     * @param  Collection<int, array<string, mixed>>  $students
     * @return Collection<int, array<string, mixed>>
     */
    private function groupByPlan($students)
    {
        return $students
            ->groupBy(fn (array $row) => $row['student']->plan?->code ?? '~none')
            ->sortKeys()
            ->map(fn ($group, string $code) => [
                'label' => $code === '~none' ? 'No study plan' : $code,
                'name' => $group->first()['student']->plan?->name,
                'students' => $group->values(),
            ])
            ->values();
    }

    /**
     * The variables shared by the full dashboard and the detail fragment.
     *
     * @return array<string, mixed>
     */
    private function payload(
        User $advisor,
        array $summary,
        $students,
        ?array $selected,
        string $tab,
        ?string $session,
        ?string $semester,
    ): array {
        return [
            'advisor' => $advisor,
            'summary' => $summary,
            'students' => $students,
            'planGroups' => $this->groupByPlan($students),
            'selected' => $selected,
            'tab' => $tab,
            'semesterLabels' => StudyPlanService::SEMESTER_LABELS,
            'activeSession' => $session,
            'activeSemester' => $semester,
        ];
    }
}
