<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\PlanCourse;
use Illuminate\Support\Collection;

/**
 * The course rows of one study plan, shared by the curriculum screens and the
 * read-only view an advisor sees for a supervisee.
 *
 * A plan's rows come from its explicit slots (plan_courses) plus the courses
 * still attached through the course directory, so every screen agrees.
 */
class StudyPlanRows
{
    /**
     * Every row of a plan, ready for a year/semester grid.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function for(CoursePlan $plan): Collection
    {
        $slots = $plan->planCourses()
            ->with('course.courseType')
            ->orderBy('year')
            ->orderBy('semester')
            ->orderBy('id')
            ->get();

        $pinnedIds = $slots->pluck('course_id')->filter()->all();

        $attached = $plan->courses()
            ->with('courseType')
            ->whereNotIn('id', $pinnedIds)
            ->orderBy('semester')
            ->orderBy('course_code')
            ->get();

        $rows = collect();

        foreach ($slots as $slot) {
            // A row can be on the plan without a term — electives are added
            // that way, for example — and it waits in "Courses without a term".
            $placed = $slot->year !== null && $slot->semester !== null;

            $rows->push([
                'key' => 'slot-'.$slot->id,
                'plan_course' => $slot,
                'course' => $slot->course,
                'code' => $slot->course?->code ?? $this->placeholderLabel($slot),
                'name' => $slot->course?->name ?? 'Elective option — the student picks the course',
                'year' => $placed ? max(1, (int) $slot->year) : null,
                'semester' => $placed ? max(1, min(3, (int) $slot->semester)) : null,
                'category' => $slot->category ?: 'required',
                'credits' => (int) ($slot->credits ?? $slot->course?->credits ?? 0),
                'slotted' => true,
                'placed' => $placed,
            ]);
        }

        foreach ($attached as $course) {
            $semester = (int) $course->semester;
            // Semesters 1–10 have a place in the curriculum; anything else
            // (the elective catalogue's 99, or an unset semester) has no term.
            $placed = $semester >= 1 && $semester <= 10;

            $rows->push([
                'key' => 'course-'.$course->id,
                'plan_course' => null,
                'course' => $course,
                'code' => $course->code,
                'name' => $course->name,
                'year' => $placed ? (int) ceil($semester / 2) : null,
                'semester' => $placed ? ($semester % 2 === 1 ? 1 : 2) : null,
                'category' => $this->defaultCategory($course),
                'credits' => (int) $course->credits,
                'slotted' => false,
                'placed' => $placed,
            ]);
        }

        return $rows;
    }

    /**
     * Years → semesters → rows, in the odd / special / even order the student
     * and advisor grids use. The special semester only appears when the plan
     * actually uses one.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{years: array<int, array<int, array{rows: Collection<int, array<string, mixed>>, credits: int}>>, semesters: array<int, int>, maxYear: int}
     */
    public function grid(Collection $rows, ?float $durationYears = null): array
    {
        $placed = $rows->where('placed', true);

        $maxYear = max(
            1,
            (int) ceil($durationYears ?? 4),
            (int) ($placed->max('year') ?? 0),
        );

        $semesters = $placed->contains(fn (array $row) => $row['semester'] === 3)
            ? [1, 3, 2]
            : [1, 2];

        $years = [];

        foreach (range(1, $maxYear) as $year) {
            foreach ($semesters as $semester) {
                $termRows = $placed
                    ->filter(fn (array $row) => $row['year'] === $year && $row['semester'] === $semester)
                    ->sortBy('code')
                    ->values();

                $years[$year][$semester] = [
                    'rows' => $termRows,
                    'credits' => $termRows->sum('credits'),
                ];
            }
        }

        return ['years' => $years, 'semesters' => $semesters, 'maxYear' => $maxYear];
    }

    /**
     * The plan category a course belongs to, taken from its course type.
     */
    public function defaultCategory(Course $course): string
    {
        $type = mb_strtoupper((string) $course->courseType?->code);

        return match (true) {
            in_array($type, [CourseType::ELECTIVE_COURSE, CourseType::ELECTIVE_OPTION], true) => 'elective',
            $type === 'CT01' => 'university',
            default => 'required',
        };
    }

    public function placeholderLabel(PlanCourse $slot): string
    {
        $label = str_replace('_', ' ', (string) $slot->placeholder_key);

        return ucfirst(mb_strtolower(trim($label))) ?: 'Elective slot';
    }
}
