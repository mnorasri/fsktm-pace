<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseOfferingRequest;
use App\Models\PlanCourse;
use App\Models\PreRegistration;
use App\Models\Setting;
use App\Models\StudyPlanEntry;
use App\Models\User;
use App\Services\CourseRequestRegistrationService;
use App\Services\CurriculumService;
use App\Support\RegistrationSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseOfferingRequestController extends Controller
{
    public function __construct(private readonly CurriculumService $curriculum) {}

    public function studentIndex(Request $request): View
    {
        return $this->listing($request, 'student');
    }

    public function advisorIndex(Request $request): View
    {
        return $this->listing($request, 'advisor');
    }

    public function hodIndex(Request $request): View
    {
        return $this->listing($request, 'hod');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'reason' => ['required', 'string', 'max:2000'],
        ], ['reason.required' => 'Please provide a reason for requesting this course.']);
        /** @var User $student */
        $student = $request->user();
        [$session, $semester] = $this->activeTerm();
        $this->ensureRequestsOpen();

        if (! $student->advisor?->isAdvisor()) {
            throw ValidationException::withMessages(['course_id' => 'You need an assigned academic advisor before requesting a course.']);
        }

        DB::transaction(function () use ($data, $student, $session, $semester) {
            $course = Course::whereKey($data['course_id'])->lockForUpdate()->firstOrFail();
            $student = User::whereKey($student->id)->lockForUpdate()->firstOrFail();

            if (! $this->eligibleCourses($student)->whereKey($course->id)->exists()) {
                throw ValidationException::withMessages(['course_id' => 'Choose an incomplete course from your assigned study plan.']);
            }

            if ($course->offerings()->where('session', $session)->where('semester', $semester)->where('is_offered', true)->exists()) {
                throw ValidationException::withMessages(['course_id' => 'This course is already offered. Please pre-register normally.']);
            }

            if (CourseOfferingRequest::where('user_id', $student->id)->where('course_id', $course->id)
                ->where('session', $session)->where('semester', $semester)->exists()) {
                throw ValidationException::withMessages(['course_id' => 'You have already requested this course for the active term.']);
            }

            $committed = $this->curriculum->totalCommittedCredits($student, $session, $semester);
            if ($committed + $course->credits > CurriculumService::MAX_CREDITS_PER_TERM) {
                throw ValidationException::withMessages([
                    'course_id' => 'Your pre-registered courses and outstanding course requests total '.$committed
                        .' credits. This request would exceed the '.CurriculumService::MAX_CREDITS_PER_TERM.'-credit term limit.',
                ]);
            }

            CourseOfferingRequest::create([
                'user_id' => $student->id, 'course_id' => $course->id,
                'session' => $session, 'semester' => $semester,
                'reason' => $data['reason'], 'status' => CourseOfferingRequest::PENDING_ADVISOR,
            ]);
        });

        return redirect()->route('student.course-requests.index')->with('status', 'Course request sent to your academic advisor.');
    }

    public function advisorReview(Request $request, CourseOfferingRequest $courseRequest): RedirectResponse
    {
        abort_unless($courseRequest->student->advisor_id === $request->user()->id, 403);
        $data = $request->validate([
            'decision' => ['required', 'in:forward,reject'],
            'note' => ['required', 'string', 'max:2000'],
        ], ['note.required' => 'Please provide your reason for forwarding or rejecting this request.']);

        DB::transaction(function () use ($courseRequest, $request, $data) {
            $record = CourseOfferingRequest::whereKey($courseRequest->id)->lockForUpdate()->firstOrFail();
            $this->ensureReviewable($record, CourseOfferingRequest::PENDING_ADVISOR);
            $forward = $data['decision'] === 'forward';
            $record->update([
                'status' => $forward ? CourseOfferingRequest::PENDING_HOD : CourseOfferingRequest::REJECTED,
                'advisor_reviewed_by' => $request->user()->id,
                'advisor_note' => $data['note'] ?? null,
                'advisor_reviewed_at' => now(),
                'forwarded_at' => $forward ? now() : null,
            ]);
        });

        return back()->with('status', $data['decision'] === 'forward'
            ? 'Request reviewed and forwarded to the Head of Department.'
            : 'Course request rejected with your feedback.');
    }

    public function hodReview(Request $request, CourseOfferingRequest $courseRequest, CourseRequestRegistrationService $registrations): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['required_if:decision,reject', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($courseRequest, $request, $data, $registrations) {
            $course = Course::whereKey($courseRequest->course_id)->lockForUpdate()->firstOrFail();
            $record = CourseOfferingRequest::whereKey($courseRequest->id)->lockForUpdate()->firstOrFail();
            $this->ensureReviewable($record, CourseOfferingRequest::PENDING_HOD);

            if (! $record->forwarded_at || ! $record->advisor_reviewed_at) {
                throw ValidationException::withMessages(['course_request' => 'The advisor must review and forward this request first.']);
            }

            if ($data['decision'] === 'approve') {
                $this->ensureRequestsOpen();
                if (! $course->is_active) {
                    throw ValidationException::withMessages(['course_request' => 'This course is inactive and cannot be offered.']);
                }
            }

            $record->update([
                'status' => $data['decision'] === 'approve' ? CourseOfferingRequest::APPROVED : CourseOfferingRequest::REJECTED,
                'hod_reviewed_by' => $request->user()->id,
                'hod_note' => $data['note'] ?? null,
                'hod_reviewed_at' => now(),
            ]);

            if ($data['decision'] === 'approve') {
                $registrations->register($record);
            }
        });

        return back()->with('status', $data['decision'] === 'approve'
            ? 'Request approved. The student is registered and included in Manage Offering.'
            : 'Course request rejected with your feedback.');
    }

    private function listing(Request $request, string $mode): View
    {
        [$session, $semester] = $this->activeTerm();
        $query = CourseOfferingRequest::with(['course', 'student.program', 'student.advisor', 'advisorReviewer', 'hodReviewer']);

        if ($mode === 'student') {
            $query->where('user_id', $request->user()->id);
        } elseif ($mode === 'advisor') {
            $query->whereHas('student', fn (Builder $students) => $students->where('advisor_id', $request->user()->id));
        } else {
            $query->whereNotNull('forwarded_at');
        }

        $courses = $mode === 'student'
            ? $this->eligibleCourses($request->user())
                ->whereDoesntHave('offerings', fn (Builder $offerings) => $offerings
                    ->where('session', $session)->where('semester', $semester)->where('is_offered', true))
                ->whereNotIn('id', CourseOfferingRequest::where('user_id', $request->user()->id)
                    ->where('session', $session)->where('semester', $semester)->select('course_id'))
                ->orderBy('course_code')->get()
            : collect();

        $records = $query->orderByRaw("CASE WHEN status IN ('pending_advisor', 'pending_hod') THEN 0 ELSE 1 END")
            ->latest('id')->paginate(15);
        $registered = PreRegistration::query()
            ->join('offerings', 'offerings.id', '=', 'pre_registrations.offering_id')
            ->where('pre_registrations.status', 'approved')
            ->whereIn('pre_registrations.user_id', $records->pluck('user_id'))
            ->whereIn('offerings.course_id', $records->pluck('course_id'))
            ->get(['pre_registrations.user_id', 'offerings.course_id', 'offerings.session', 'offerings.semester'])
            ->mapWithKeys(fn ($row) => [json_encode([$row->user_id, (int) $row->course_id, $row->session, $row->semester]) => true]);
        $registeredRequestIds = $records->getCollection()
            ->filter(fn ($record) => $registered->has(json_encode([$record->user_id, (int) $record->course_id, $record->session, $record->semester])))
            ->modelKeys();

        return view('course-requests.index', [
            'requests' => $records,
            'registeredRequestIds' => $registeredRequestIds,
            'courses' => $courses,
            'mode' => $mode,
            'activeSession' => $session,
            'activeSemester' => $semester,
            'requestsClosed' => $this->requestsClosed(),
            'committedCredits' => $mode === 'student'
                ? $this->curriculum->totalCommittedCredits($request->user(), $session, $semester)
                : null,
        ]);
    }

    private function eligibleCourses(User $student): Builder
    {
        return Course::where('is_active', true)
            ->where(function (Builder $courses) use ($student) {
                $courses->whereIn('id', StudyPlanEntry::where('user_id', $student->id)->whereNotNull('course_id')->select('course_id'));
                if ($student->plan_id) {
                    $courses->orWhere('course_plan_id', $student->plan_id)
                        ->orWhereIn('id', PlanCourse::where('course_plan_id', $student->plan_id)->whereNotNull('course_id')->select('course_id'));
                }
            })
            ->whereNotIn('id', StudyPlanEntry::where('user_id', $student->id)
                ->whereIn('status', ['COMPLETED', 'EXEMPTED'])->whereNotNull('course_id')->select('course_id'));
    }

    private function ensureReviewable(CourseOfferingRequest $record, string $status): void
    {
        [$session, $semester] = $this->activeTerm();
        if ($record->status !== $status || $record->session !== $session || $record->semester !== $semester) {
            throw ValidationException::withMessages(['course_request' => 'This request is no longer awaiting this review for the active term.']);
        }
    }

    private function requestsClosed(): bool
    {
        $cutoff = RegistrationSchedule::registrationCutoff();

        return $cutoff !== null && CarbonImmutable::today()->gt($cutoff);
    }

    private function ensureRequestsOpen(): void
    {
        if ($this->requestsClosed()) {
            throw ValidationException::withMessages(['course_request' => 'Course requests are closed because the registration deadline has passed.']);
        }
    }

    /** @return array{0: string, 1: string} */
    private function activeTerm(): array
    {
        return [
            Setting::get('active_session', '2025/2026'),
            Setting::get('active_semester', '1'),
        ];
    }
}
