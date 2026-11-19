<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseOfferingRequest;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class CurriculumService
{
    /** Hard credit cap per academic term. */
    public const MAX_CREDITS_PER_TERM = 20;

    /**
     * Every study-plan entry for a student keyed by course code.
     *
     * @return Collection<string, StudyPlanEntry>
     */
    public function entryMap(User $student): Collection
    {
        $student->loadMissing('studyPlanEntries.course');

        return $student->studyPlanEntries
            ->filter(fn (StudyPlanEntry $entry) => $entry->course !== null)
            ->keyBy(fn (StudyPlanEntry $entry) => $entry->course->code);
    }

    /**
     * Evaluate prerequisite standing for a course.
     *
     * $strict = true  → prerequisites must already be COMPLETED/EXEMPTED
     *                   (used for pre-registration locking).
     * $strict = false → a prerequisite planned in an *earlier* term is
     *                   accepted, and anything planned in the same or a
     *                   later term is reported as a suspension warning.
     *
     * @return array{ok:bool, missing:array<int,string>, late:array<int,array{code:string,term:string}>}
     */
    public function checkPrerequisites(User $student, Course $course, StudyPlanEntry $entry, bool $strict): array
    {
        $codes = $course->prerequisiteCodes();

        if (empty($codes)) {
            return ['ok' => true, 'missing' => [], 'late' => []];
        }

        $entries = $this->entryMap($student);
        $missing = [];
        $late = [];

        foreach ($codes as $code) {
            /** @var StudyPlanEntry|null $prereq */
            $prereq = $entries->get($code);

            if (! $prereq) {
                $missing[] = $code;

                continue;
            }

            if (in_array($prereq->status, ['COMPLETED', 'EXEMPTED'], true)) {
                continue;
            }

            if ($strict) {
                $missing[] = $code;

                continue;
            }

            if ($prereq->orderKey() >= $entry->orderKey()) {
                $late[] = [
                    'code' => $code,
                    'term' => "Year {$prereq->year} Sem {$prereq->semester}",
                ];
            }
        }

        return [
            'ok' => empty($missing) && empty($late),
            'missing' => $missing,
            'late' => $late,
        ];
    }

    /**
     * Prerequisite standing is informational during registration.
     *
     * @return array{ok:bool, missing:array<int,string>}
     */
    public function prerequisitesSatisfied(User $student, Course $course): array
    {
        $missing = array_column(array_filter(
            $this->prerequisiteStanding($student, $course),
            fn (array $prerequisite) => ! $prerequisite['met'],
        ), 'code');

        return ['ok' => empty($missing), 'missing' => $missing];
    }

    /** @return list<array{code: string, status: string, met: bool}> */
    public function prerequisiteStanding(User $student, Course $course): array
    {
        $codes = $course->prerequisiteCodes();
        if ($codes === []) {
            return [];
        }

        $entries = $this->entryMap($student);

        return array_map(function (string $code) use ($entries): array {
            $status = $entries->get($code)?->status ?? 'NOT_COMPLETED';

            return [
                'code' => $code,
                'status' => $status,
                'met' => in_array($status, ['COMPLETED', 'EXEMPTED'], true),
            ];
        }, $codes);
    }

    /**
     * Credits already committed to a term (pending + approved).
     */
    public function committedCredits(User $student, string $session, string $semester): int
    {
        return (int) $student->preRegistrations()
            ->where('session', $session)
            ->where('semester', $semester)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('credits');
    }

    /**
     * Registrations plus outstanding requests, counting each course once.
     * A request stops reserving extra credits when its registration exists.
     */
    public function totalCommittedCredits(User $student, string $session, string $semester, ?int $excludeRequestedCourseId = null): int
    {
        $requested = CourseOfferingRequest::query()
            ->join('courses', 'courses.id', '=', 'course_offering_requests.course_id')
            ->where('course_offering_requests.user_id', $student->id)
            ->where('course_offering_requests.session', $session)
            ->where('course_offering_requests.semester', $semester)
            ->whereIn('course_offering_requests.status', [
                CourseOfferingRequest::PENDING_ADVISOR, CourseOfferingRequest::PENDING_HOD, CourseOfferingRequest::APPROVED,
            ])
            ->when($excludeRequestedCourseId !== null, fn ($query) => $query->where('courses.id', '!=', $excludeRequestedCourseId))
            ->whereNotExists(function (Builder $query) {
                $query->selectRaw('1')->from('pre_registrations')
                    ->join('offerings', 'offerings.id', '=', 'pre_registrations.offering_id')
                    ->whereColumn('pre_registrations.user_id', 'course_offering_requests.user_id')
                    ->whereColumn('offerings.course_id', 'course_offering_requests.course_id')
                    ->whereColumn('pre_registrations.session', 'course_offering_requests.session')
                    ->whereColumn('pre_registrations.semester', 'course_offering_requests.semester')
                    ->whereIn('pre_registrations.status', ['pending', 'approved']);
            })
            ->sum('courses.credit_hours');

        return $this->committedCredits($student, $session, $semester) + (int) $requested;
    }
}
