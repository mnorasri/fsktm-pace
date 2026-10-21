<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Support\Collection;

class CurriculumService
{
    /** Hard credit cap per academic term. */
    public const MAX_CREDITS_PER_TERM = 20;

    /**
     * Cached per-student entry maps, so laying out a plan does not re-query
     * for every prerequisite check on the page.
     *
     * @var array<int, Collection<string, StudyPlanEntry>>
     */
    private array $entryMaps = [];

    /**
     * Every study-plan entry for a student keyed by course code.
     *
     * @return Collection<string, StudyPlanEntry>
     */
    public function entryMap(User $student): Collection
    {
        return $this->entryMaps[$student->id] ??= $student->studyPlanEntries()
            ->with('course')
            ->get()
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
     * Strict prerequisite gate used for pre-registration: every prerequisite
     * must already be COMPLETED or EXEMPTED.
     *
     * @return array{ok:bool, missing:array<int,string>}
     */
    public function prerequisitesSatisfied(User $student, Course $course): array
    {
        $codes = $course->prerequisiteCodes();

        if (empty($codes)) {
            return ['ok' => true, 'missing' => []];
        }

        $entries = $this->entryMap($student);
        $missing = [];

        foreach ($codes as $code) {
            $entry = $entries->get($code);

            if (! $entry || ! in_array($entry->status, ['COMPLETED', 'EXEMPTED'], true)) {
                $missing[] = $code;
            }
        }

        return ['ok' => empty($missing), 'missing' => $missing];
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
}
