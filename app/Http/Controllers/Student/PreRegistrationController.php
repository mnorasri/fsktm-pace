<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Offering;
use App\Models\PreRegistration;
use App\Models\RegistrationSubmission;
use App\Models\Setting;
use App\Models\User;
use App\Services\CurriculumService;
use App\Services\StudyPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PreRegistrationController extends Controller
{
    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly StudyPlanService $plans,
    ) {}

    public function index(): View
    {
        /** @var User $student */
        $student = auth()->user();

        [$session, $semester] = $this->activeTerm();

        // Course registration only opens once the advisor has approved the
        // study plan it is built from.
        $planApproved = $student->plan_status === 'approved';

        // The term's pre-registration sits with the advisor: while it is with
        // them (or already approved) the student cannot change it.
        $submission = RegistrationSubmission::forTerm($student, $session, $semester);
        $registrationLocked = $submission?->isLocked() ?? false;

        $registrations = $student->preRegistrations()
            ->with('offering.course')
            ->where('session', $session)
            ->where('semester', $semester)
            ->get()
            ->keyBy('offering_id');

        // The courses the HoD published for the active term, keyed by course.
        $offerings = Offering::with(['course.courseType', 'lecturer', 'coordinator'])
            ->where('session', $session)
            ->where('semester', $semester)
            ->where('is_offered', true)
            ->get()
            ->keyBy('course_id');

        // The saved study plan supplies the list — the same odd / special /
        // even grid the student sees on their Study Plan page — and the
        // offering decides which rows may actually be added this term.
        $entries = $this->plans->entriesFor($student);
        $grid = $this->plans->grid($student, $entries);

        $committed = $this->curriculum->committedCredits($student, $session, $semester);
        $cap = CurriculumService::MAX_CREDITS_PER_TERM;

        $rows = [];

        foreach ($entries as $entry) {
            $course = $entry->course;
            $offering = $course ? $offerings->get($course->id) : null;
            $registration = $offering ? $registrations->get($offering->id) : null;
            $selected = $registration !== null;

            $eligible = false;
            $capBlocked = false;
            $reason = null;

            if (! $course) {
                $reason = 'Elective placeholder — replace it in your study plan first.';
            } elseif (! $offering) {
                $reason = 'Not offered in '.$session.' · Semester '.$semester.'.';
            } elseif (! $selected) {
                if ($registrationLocked) {
                    $reason = $submission->status === RegistrationSubmission::STATUS_APPROVED
                        ? 'Registration approved — locked for this term.'
                        : 'Awaiting your advisor’s approval.';
                } elseif (! $planApproved) {
                    $reason = 'Awaiting approval of your study plan.';
                } else {
                    $prereq = $this->curriculum->prerequisitesSatisfied($student, $course);
                    $eligible = $prereq['ok'];

                    if (! $eligible) {
                        $reason = 'Locked — complete prerequisite(s): '.implode(', ', $prereq['missing']).'.';
                    } elseif ($committed + $course->credits > $cap) {
                        $capBlocked = true;
                        $reason = 'Credit cap reached — remove a course before adding '.$course->code.'.';
                    }
                }
            }

            $rows[$entry->id] = [
                'course' => $course,
                'offering' => $offering,
                'registration' => $registration,
                'selected' => $selected,
                'capBlocked' => $capBlocked,
                'awaitingPlan' => $offering !== null && ! $selected && ! $planApproved && ! $registrationLocked,
                'addable' => ! $registrationLocked && $planApproved && $offering !== null && ! $selected && $eligible && ! $capBlocked,
                'reason' => $reason,
            ];
        }

        return view('student.pre-registration', [
            'student' => $student,
            'years' => $grid['years'],
            'semesters' => $grid['semesters'],
            'semesterLabels' => StudyPlanService::SEMESTER_LABELS,
            'rows' => $rows,
            'registrations' => $registrations,
            'offeredCount' => $offerings->count(),
            'offeredInPlan' => collect($rows)->filter(fn (array $row) => $row['offering'] !== null)->count(),
            'committedCredits' => $committed,
            'cap' => $cap,
            'remainingCredits' => max(0, $cap - $committed),
            'planApproved' => $planApproved,
            'submission' => $submission,
            'registrationLocked' => $registrationLocked,
            'registrationCount' => $registrations->count(),
            'activeSession' => $session,
            'activeSemester' => $semester,
        ]);
    }

    public function store(Offering $offering): RedirectResponse
    {
        /** @var User $student */
        $student = auth()->user();

        [$session, $semester] = $this->activeTerm();

        $submission = RegistrationSubmission::forTerm($student, $session, $semester);

        if ($submission?->isLocked()) {
            return back()->withErrors([
                'offering' => $submission->status === RegistrationSubmission::STATUS_APPROVED
                    ? 'Your pre-registration has been approved by your advisor and is now locked.'
                    : 'Your pre-registration is with your advisor for approval. You cannot change it until they respond.',
            ]);
        }

        if ($student->plan_status !== 'approved') {
            return back()->withErrors([
                'offering' => 'Your study plan must be approved by your advisor before you can register for courses.',
            ]);
        }

        if ($offering->session !== $session || $offering->semester !== $semester || ! $offering->is_offered) {
            return back()->withErrors(['offering' => 'That course is not offered in the active term.']);
        }

        $existing = $student->preRegistrations()
            ->where('offering_id', $offering->id)
            ->first();

        if ($existing && in_array($existing->status, ['pending', 'approved'], true)) {
            return back()->withErrors(['offering' => 'You have already pre-registered for '.$offering->course->code.'.']);
        }

        $prereq = $this->curriculum->prerequisitesSatisfied($student, $offering->course);

        if (! $prereq['ok']) {
            return back()->withErrors([
                'offering' => $offering->course->code.' is locked. Complete the prerequisite(s): '.implode(', ', $prereq['missing']).'.',
            ]);
        }

        $committed = $this->curriculum->committedCredits($student, $session, $semester);

        if ($committed + $offering->course->credits > CurriculumService::MAX_CREDITS_PER_TERM) {
            $remaining = max(0, CurriculumService::MAX_CREDITS_PER_TERM - $committed);

            return back()->withErrors([
                'offering' => 'Credit cap reached. You may only add '.$remaining.' more credit(s) this term (maximum '.CurriculumService::MAX_CREDITS_PER_TERM.').',
            ]);
        }

        // Idempotent: re-requesting a previously rejected course resets it to pending.
        PreRegistration::updateOrCreate(
            ['user_id' => $student->id, 'offering_id' => $offering->id],
            [
                'session' => $session,
                'semester' => $semester,
                'credits' => $offering->course->credits,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ],
        );

        return back()->with('status', $offering->course->code.' added to your pre-registration (pending approval).');
    }

    public function destroy(PreRegistration $registration): RedirectResponse
    {
        abort_unless($registration->user_id === auth()->id(), 403);
        abort_unless($registration->status === 'pending', 403, 'Approved registrations cannot be removed.');

        /** @var User $student */
        $student = auth()->user();
        [$session, $semester] = $this->activeTerm();
        $submission = RegistrationSubmission::forTerm($student, $session, $semester);

        abort_if(
            $submission?->isLocked(),
            403,
            $submission->status === RegistrationSubmission::STATUS_APPROVED
                ? 'Your pre-registration has been approved and can no longer be changed.'
                : 'Your pre-registration is awaiting your advisor\'s approval and can no longer be changed.',
        );

        $registration->delete();

        return back()->with('status', 'Pre-registration request removed.');
    }

    /**
     * Send the term's pre-registration to the advisor as one batch.
     */
    public function submit(): RedirectResponse
    {
        /** @var User $student */
        $student = auth()->user();

        [$session, $semester] = $this->activeTerm();

        if ($student->plan_status !== 'approved') {
            return back()->withErrors([
                'registration' => 'Your study plan must be approved by your advisor before you can submit a pre-registration.',
            ]);
        }

        $submission = RegistrationSubmission::forTerm($student, $session, $semester);

        if ($submission?->isLocked()) {
            return back()->withErrors([
                'registration' => $submission->status === RegistrationSubmission::STATUS_APPROVED
                    ? 'Your pre-registration for this term has already been approved.'
                    : 'Your pre-registration is already with your advisor for approval.',
            ]);
        }

        $requests = $student->preRegistrations()
            ->where('session', $session)
            ->where('semester', $semester)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($requests === 0) {
            return back()->withErrors([
                'registration' => 'Add at least one course before submitting your pre-registration to your advisor.',
            ]);
        }

        RegistrationSubmission::updateOrCreate(
            ['user_id' => $student->id, 'session' => $session, 'semester' => $semester],
            [
                'status' => RegistrationSubmission::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'note' => null,
            ],
        );

        return back()->with('status', 'Pre-registration submitted to your advisor for approval.');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function activeTerm(): array
    {
        return [
            Setting::get('active_session', '2025/2026'),
            Setting::get('active_semester', '1'),
        ];
    }
}
