<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseType;
use App\Models\StudyPlanEntry;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shared study-plan logic: seeding the interactive plan, keeping either/or
 * groups to a single choice, and laying the entries out in the odd / special /
 * even term grid the student pages share.
 */
class StudyPlanService
{
    /** Term labels used by the study grid and the move control. */
    public const SEMESTER_LABELS = [
        1 => 'Semester 1',
        2 => 'Semester 2',
        3 => 'Special Semester',
    ];

    /**
     * Seed the plan when it is still empty, settle the either/or groups, then
     * load the entries with everything the grids need.
     *
     * @return Collection<int, StudyPlanEntry>
     */
    public function entriesFor(User $student): Collection
    {
        $this->ensurePlanEntries($student);
        $this->resolveAlternativeGroups($student);

        return StudyPlanEntry::with([
            'course.courseType',
            'course.department',
            'course.mqfSc',
            'electiveSlot.courseType',
        ])
            ->where('user_id', $student->id)
            ->get();
    }

    /**
     * The courses a student may pick for their "Elective Course" slots: the
     * elective catalogue of their own study plan (course type CT07).
     *
     * @return Collection<int, Course>
     */
    public function electiveCoursesFor(User $student): Collection
    {
        if (! $student->plan_id) {
            return collect();
        }

        return Course::with('courseType')
            ->where('course_plan_id', $student->plan_id)
            ->whereHas('courseType', fn ($query) => $query->where('code', CourseType::ELECTIVE_COURSE))
            ->orderBy('course_code')
            ->get();
    }

    /**
     * The elective catalogue is not taught in a term of its own — it exists to
     * fill the Elective Option slots — so it is never seeded as a plan row.
     */
    public function isElectivePool(?Course $course): bool
    {
        return $course !== null
            && mb_strtoupper((string) $course->courseType?->code) === CourseType::ELECTIVE_COURSE;
    }

    /**
     * Years → semesters → entries. Each year is a row: the odd semester, the
     * even semester and — only when the plan uses one — the special semester,
     * which sits between the other two.
     *
     * @param  Collection<int, StudyPlanEntry>  $entries
     * @return array{years: array<int, array<int, array{entries: Collection<int, StudyPlanEntry>, credits: int}>>, semesters: array<int, int>, maxYear: int}
     */
    public function grid(User $student, Collection $entries): array
    {
        // Round partial years up (a 3.5-year programme still needs four year
        // rows, the last one half-length).
        $maxYear = max(
            (int) ceil((float) ($student->plan?->program?->duration_years ?? 4)),
            (int) ($entries->max('year') ?? 4),
        );

        $semesters = $entries->contains(fn (StudyPlanEntry $entry) => (int) $entry->semester === 3)
            ? [1, 3, 2]
            : [1, 2];

        $years = [];

        foreach (range(1, $maxYear) as $year) {
            foreach ($semesters as $semester) {
                $termEntries = $entries
                    ->filter(fn (StudyPlanEntry $entry) => (int) $entry->year === $year && (int) $entry->semester === $semester)
                    ->sortBy(fn (StudyPlanEntry $entry) => $entry->course->code ?? 'zzz');

                $years[$year][$semester] = [
                    'entries' => $termEntries,
                    'credits' => $termEntries->sum(fn (StudyPlanEntry $entry) => $entry->course->credits ?? 0),
                ];
            }
        }

        return ['years' => $years, 'semesters' => $semesters, 'maxYear' => $maxYear];
    }

    /**
     * Entry id → the semesters it may be moved into.
     *
     * @param  Collection<int, StudyPlanEntry>  $entries
     * @return Collection<int, array<int, int>>
     */
    public function movableSemesters(Collection $entries): Collection
    {
        return $entries->mapWithKeys(fn (StudyPlanEntry $entry) => [$entry->id => $this->semestersFor($entry)]);
    }

    /**
     * Semesters an entry may be moved into. Parity is preserved — a Semester 1
     * course only moves between Semester 1 terms — except for industrial
     * training, which the plan may place in an odd or even semester.
     *
     * @return array<int, int>
     */
    public function semestersFor(StudyPlanEntry $entry): array
    {
        $own = (int) $entry->semester;

        if (! $this->isIndustrialTraining($entry)) {
            return [$own];
        }

        // Both parities, plus the entry's own semester so a special semester
        // slot can always be returned to.
        return array_values(array_unique([1, 2, $own]));
    }

    /**
     * The either/or course choices available to a student, keyed by group.
     *
     * @return Collection<int|string, Collection<int, Course>>
     */
    public function alternativeGroupsFor(User $student): Collection
    {
        if (! $student->plan_id) {
            return collect();
        }

        return Course::where('course_plan_id', $student->plan_id)
            ->whereNotNull('alternative_group')
            ->where('alternative_group', '!=', '')
            ->orderBy('course_code')
            ->get()
            ->groupBy('alternative_group');
    }

    /**
     * Drop every other member of the entry's alternative group, so a plan only
     * ever carries one option.
     */
    public function dropOtherAlternatives(StudyPlanEntry $entry): void
    {
        $group = $entry->course?->alternative_group;

        if (! $group) {
            return;
        }

        StudyPlanEntry::where('user_id', $entry->user_id)
            ->whereKeyNot($entry->getKey())
            ->whereHas('course', fn ($query) => $query->where('alternative_group', $group))
            ->delete();
    }

    /**
     * Year a curriculum course sits in: Semester 3 → Year 2, Semester 4 → Year 2.
     */
    public function curriculumYear(Course $course): int
    {
        return max(1, (int) ceil(max(1, (int) $course->semester) / 2));
    }

    /**
     * Odd curriculum semesters map to Semester 1, even ones to Semester 2.
     */
    public function curriculumSemester(Course $course): int
    {
        return max(1, (int) $course->semester) % 2 === 1 ? 1 : 2;
    }

    public function semesterLabel(int|string|null $semester): string
    {
        return self::SEMESTER_LABELS[(int) $semester] ?? 'Semester '.(int) $semester;
    }

    public function entryLabel(StudyPlanEntry $entry): string
    {
        return $entry->course?->code ?? $entry->placeholder_key ?? 'Course';
    }

    private function isIndustrialTraining(StudyPlanEntry $entry): bool
    {
        $type = $entry->course?->courseType;

        return $type !== null
            && str_contains(mb_strtolower($type->name.' '.$type->code), 'industrial training');
    }

    /**
     * Seed the interactive study plan from the student's curriculum the first
     * time they open a student page that shows it.
     */
    private function ensurePlanEntries(User $student): void
    {
        $plan = $student->plan;

        if (! $plan) {
            return;
        }

        // Seeded rows carrying neither a course nor a placeholder key come from
        // incomplete plan_courses data — there is nothing to render for them, so
        // clear them out before deciding whether the plan still needs seeding.
        $student->studyPlanEntries()
            ->whereNull('course_id')
            ->where(fn ($query) => $query->whereNull('placeholder_key')->orWhere('placeholder_key', ''))
            ->delete();

        if ($student->studyPlanEntries()->exists()) {
            return;
        }

        $candidates = [];

        // Slots explicitly defined on the study plan: real courses and the
        // elective placeholders the student replaces later.
        foreach ($plan->planCourses()->with('course')->orderBy('year')->orderBy('semester')->get() as $planCourse) {
            if (! $planCourse->course_id && ! $planCourse->placeholder_key) {
                continue;
            }

            $candidates[] = [
                'course' => $planCourse->course,
                'placeholder_key' => $planCourse->placeholder_key ?: null,
                'year' => max(1, (int) $planCourse->year),
                // 1 = odd, 2 = even, 3 = the short special semester.
                'semester' => max(1, min(3, (int) $planCourse->semester)),
            ];
        }

        // The plan's own courses, placed by their curriculum semester: odd
        // semesters sit in Semester 1 of the year, even ones in Semester 2.
        $slotted = array_filter(array_map(fn (array $row) => $row['course']?->id, $candidates));

        foreach ($plan->courses()->with('courseType')->orderBy('semester')->orderBy('course_code')->get() as $course) {
            if (in_array($course->id, $slotted, true)) {
                continue;
            }

            // The elective catalogue fills Elective Option slots instead of
            // sitting in a term of its own.
            if ($this->isElectivePool($course)) {
                continue;
            }

            $candidates[] = [
                'course' => $course,
                'placeholder_key' => null,
                'year' => $this->curriculumYear($course),
                'semester' => $this->curriculumSemester($course),
            ];
        }

        // Either/or groups contribute a single member — the lowest course code.
        $defaults = [];

        foreach ($candidates as $candidate) {
            $group = $candidate['course']?->alternative_group;

            if ($group && (! isset($defaults[$group]) || $candidate['course']->code < $defaults[$group])) {
                $defaults[$group] = $candidate['course']->code;
            }
        }

        foreach ($candidates as $candidate) {
            $course = $candidate['course'];
            $group = $course?->alternative_group;

            if ($group && $defaults[$group] !== $course->code) {
                continue;
            }

            if (! $course && ! $candidate['placeholder_key']) {
                continue;
            }

            StudyPlanEntry::create([
                'user_id' => $student->id,
                'course_plan_id' => $plan->id,
                'course_id' => $course?->id,
                'placeholder_key' => $candidate['placeholder_key'],
                'year' => $candidate['year'],
                'semester' => $candidate['semester'],
                'status' => 'PLANNED',
            ]);
        }
    }

    /**
     * Every plan carries exactly one course per alternative group: trim a plan
     * that holds both options down to the default, and re-add the choice when a
     * group has gone missing.
     */
    private function resolveAlternativeGroups(User $student): void
    {
        $groups = $this->alternativeGroupsFor($student);

        if ($groups->isEmpty()) {
            return;
        }

        $selected = $student->studyPlanEntries()
            ->with('course')
            ->get()
            ->filter(fn (StudyPlanEntry $entry) => $entry->course?->alternative_group)
            ->groupBy(fn (StudyPlanEntry $entry) => $entry->course->alternative_group);

        foreach ($groups as $group => $options) {
            $members = $selected->get($group) ?? collect();

            if ($members->isEmpty()) {
                $default = $options->first();

                StudyPlanEntry::create([
                    'user_id' => $student->id,
                    'course_plan_id' => $student->plan_id,
                    'course_id' => $default->id,
                    'year' => $this->curriculumYear($default),
                    'semester' => $this->curriculumSemester($default),
                    'status' => 'PLANNED',
                ]);

                continue;
            }

            // Lowest course code wins — the programme's conventional default.
            $keep = $members->sortBy(fn (StudyPlanEntry $entry) => $entry->course->code)->first();

            foreach ($members as $member) {
                if ($member->id !== $keep->id) {
                    $member->delete();
                }
            }
        }
    }
}
