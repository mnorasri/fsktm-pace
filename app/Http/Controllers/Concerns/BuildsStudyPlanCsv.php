<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\PlanCourse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The course rows of one study plan and their CSV download. A plan's rows come
 * from its explicit slots plus the courses still attached through the course
 * directory, so the grid and the export always agree.
 */
trait BuildsStudyPlanCsv
{
    /**
     * Every row of a plan, ready for the year/semester grid.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function rowsFor(CoursePlan $plan): Collection
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
     * Download one plan's curriculum as CSV: the same rows as the grid, in
     * term order. Courses without a term come last with empty term cells.
     */
    protected function studyPlanCsv(CoursePlan $plan): StreamedResponse
    {
        $rows = $this->rowsFor($plan)
            ->sortBy(fn (array $row) => sprintf(
                '%02d%02d%s',
                $row['year'] ?? 99,
                $row['semester'] ?? 9,
                $row['code'],
            ))
            ->values();

        $filename = 'study-plan-'.(Str::slug($plan->code) ?: 'plan').'.csv';

        return Response::streamDownload(function () use ($plan, $rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'study_plan_code', 'course_code', 'course_name',
                'year', 'semester', 'category', 'credits',
            ], ',', '"', '\\');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $plan->code,
                    $row['code'],
                    $row['name'],
                    $row['year'],
                    $row['semester'],
                    $row['category'],
                    $row['credits'],
                ], ',', '"', '\\');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * The plan category a course belongs to, taken from its course type.
     */
    protected function defaultCategory(Course $course): string
    {
        $type = mb_strtoupper((string) $course->courseType?->code);

        return match (true) {
            in_array($type, [CourseType::ELECTIVE_COURSE, CourseType::ELECTIVE_OPTION], true) => 'elective',
            $type === 'CT01' => 'university',
            default => 'required',
        };
    }

    protected function placeholderLabel(PlanCourse $slot): string
    {
        $label = str_replace('_', ' ', (string) $slot->placeholder_key);

        return ucfirst(mb_strtolower(trim($label))) ?: 'Elective slot';
    }
}
