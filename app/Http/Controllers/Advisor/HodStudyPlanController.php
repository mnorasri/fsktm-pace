<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\PlanCourse;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Head of Department study plans: view a programme's curriculum year by year,
 * create a new plan, place courses that already exist in the course directory
 * onto it, and bulk-import them from a CSV.
 *
 * Every placed course lives in its own plan_courses row (plan + course + term),
 * so the same course can sit in several different study plans at once.
 */
class HodStudyPlanController extends Controller
{
    use ImportsCsv;

    public const SEMESTER_LABELS = [
        1 => 'Semester 1',
        2 => 'Semester 2',
        3 => 'Special Semester',
    ];

    public function index(Request $request): View
    {
        $plans = CoursePlan::query()
            ->with('program.department')
            ->withCount(['planCourses', 'courses', 'students'])
            ->orderBy('code')
            ->get();

        $requested = $plans->firstWhere('id', (int) $request->integer('plan_id'));

        // Open on the first plan that actually carries courses, so an empty
        // plan does not look like a broken page.
        $plan = $requested
            ?? $plans->first(fn (CoursePlan $option) => $option->plan_courses_count + $option->courses_count > 0)
            ?? $plans->first();

        $rows = $plan ? $this->rowsFor($plan) : collect();
        $grid = $plan ? $this->gridFor($plan, $rows) : ['years' => [], 'semesters' => [1, 2], 'maxYear' => 4];

        $placed = $rows->where('placed', true);

        return view('advisor.hod.plans', [
            'plans' => $plans,
            'plan' => $plan,
            'years' => $grid['years'],
            'semesters' => $grid['semesters'],
            'maxYear' => $grid['maxYear'],
            'unplaced' => $rows->where('placed', false)->values(),
            'courses' => Course::with('courseType')->orderBy('course_code')->get(),
            'programs' => Program::with('department')->orderBy('code')->get(),
            'semesterLabels' => self::SEMESTER_LABELS,
            'totals' => [
                'courses' => $rows->count(),
                'credits' => $placed->sum('credits'),
                'pinned' => $rows->where('slotted', true)->count(),
            ],
        ]);
    }

    /**
     * Create an empty plan. Courses are added from the plan page afterwards.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'code' => ['required', 'string', 'max:60', 'unique:course_plans,code'],
            'name' => ['required', 'string', 'max:180'],
            'version' => ['nullable', 'string', 'max:40'],
            'total_credits' => ['nullable', 'integer', 'min:1', 'max:300'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = CoursePlan::create($data);

        return redirect()
            ->route('advisor.plans.index', ['plan_id' => $plan->id])
            ->with('status', 'Study plan '.$plan->code.' created — add its courses below.');
    }

    /**
     * Put an existing course on the plan, or pin an attached course to the
     * term chosen here.
     */
    public function addCourse(Request $request, CoursePlan $plan): RedirectResponse
    {
        $data = $this->validatedRow($request);

        $course = Course::findOrFail($data['course_id']);

        $already = $plan->planCourses()->where('course_id', $course->id)->exists();

        if ($already) {
            return back()->withErrors([
                'course_id' => $course->code.' is already placed in this study plan — edit that row instead.',
            ]);
        }

        $plan->planCourses()->create([
            'course_id' => $course->id,
            'year' => $data['year'],
            'semester' => $data['semester'],
            'category' => $data['category'] ?? $this->defaultCategory($course),
            'credits' => $data['credits'] ?? $course->credits,
        ]);

        return back()->with('status', $course->code.' placed in '.$plan->code.' — Year '.$data['year'].' '.$this->semesterLabel($data['semester']).'.');
    }

    /**
     * Bulk-import the current study plan's courses from a CSV with the columns
     * course_code, year, semester. Rows are upserted per plan so one course can
     * be part of several study plans.
     */
    public function importCourses(Request $request, CoursePlan $plan): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $created = 0;
        $updated = 0;
        $failures = [];

        foreach ($this->readCsv($request->file('file')) as $index => $row) {
            try {
                $result = $this->importPlanCourseRow($plan, $row);

                $result === 'created' ? $created++ : $updated++;
            } catch (Throwable $e) {
                $failures[] = 'Line '.($index + 2).': '.$this->failureReason($e);
            }
        }

        $report = $this->importReport('Course import', $created, $updated, $failures);

        return back()
            ->with('status', $plan->code.' course import: '.$created.' added, '.$updated.' updated, '.$report['failed'].' failed.')
            ->with('import_report', $report);
    }

    /**
     * Import one plan row: course_code, year, semester. The row commits on its
     * own so a failure never leaves a partial course on the plan.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importPlanCourseRow(CoursePlan $plan, array $row): string
    {
        $courseCode = $this->csvValue($row, 'course_code', 0) ?? $this->csvValue($row, 'code', 0);

        if (blank($courseCode)) {
            throw new RuntimeException('course_code is required');
        }

        $course = Course::whereRaw('LOWER(course_code) = ?', [mb_strtolower($courseCode)])->first();

        if (! $course) {
            throw new RuntimeException('course_code '.$courseCode.' does not exist');
        }

        $year = $this->csvValue($row, 'year', 1);
        $semester = $this->csvValue($row, 'semester', 2);

        if (blank($year) || ! ctype_digit($year) || (int) $year < 1 || (int) $year > 8) {
            throw new RuntimeException('year must be a whole number between 1 and 8');
        }

        if (blank($semester) || ! ctype_digit($semester) || ! in_array((int) $semester, [1, 2, 3], true)) {
            throw new RuntimeException('semester must be 1, 2 or 3');
        }

        return DB::transaction(function () use ($plan, $course, $year, $semester) {
            $row = $plan->planCourses()->where('course_id', $course->id)->first();

            $attributes = [
                'year' => (int) $year,
                'semester' => (int) $semester,
                'category' => $this->defaultCategory($course),
                'credits' => $course->credit_hours,
            ];

            if ($row) {
                $row->update($attributes);

                return 'updated';
            }

            $plan->planCourses()->create(['course_id' => $course->id] + $attributes);

            return 'created';
        });
    }

    /**
     * Move a placed course to another year/semester, or change its category and
     * credits. Applies to real courses and to the elective placeholder slots.
     */
    public function updateCourse(Request $request, CoursePlan $plan, PlanCourse $planCourse): RedirectResponse
    {
        abort_unless((int) $planCourse->course_plan_id === (int) $plan->id, 404);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1', 'max:8'],
            'semester' => ['required', 'integer', 'in:1,2,3'],
            'category' => ['required', 'in:required,elective,university'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $planCourse->update($data);

        $label = $planCourse->course?->code ?? $planCourse->placeholder_key ?? 'Row';

        return back()->with('status', $label.' moved to Year '.$data['year'].' '.$this->semesterLabel($data['semester']).'.');
    }

    /**
     * Take a placed course (or an elective slot) off the plan's timetable. A
     * course attached through the course directory comes back as an unplaced
     * course, ready to be put in another term.
     */
    public function removeCourse(CoursePlan $plan, PlanCourse $planCourse): RedirectResponse
    {
        abort_unless((int) $planCourse->course_plan_id === (int) $plan->id, 404);

        $label = $planCourse->course?->code ?? $planCourse->placeholder_key ?? 'Row';

        $planCourse->delete();

        return back()->with('status', $label.' removed from the '.$plan->code.' timetable.');
    }

    /**
     * Detach a course from the plan altogether. The course itself stays in the
     * course directory.
     */
    public function detachCourse(CoursePlan $plan, Course $course): RedirectResponse
    {
        abort_unless((int) $course->course_plan_id === (int) $plan->id, 404);

        $course->update(['course_plan_id' => null]);

        return back()->with('status', $course->code.' removed from '.$plan->code.'. The course itself is untouched.');
    }

    /**
     * Every row of a plan, from its explicit slots plus the courses attached to
     * it, ready for the year/semester grid.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function rowsFor(CoursePlan $plan): Collection
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
            $rows->push([
                'key' => 'slot-'.$slot->id,
                'plan_course' => $slot,
                'course' => $slot->course,
                'code' => $slot->course?->code ?? $this->placeholderLabel($slot),
                'name' => $slot->course?->name ?? 'Elective option — the student picks the course',
                'year' => max(1, (int) $slot->year),
                'semester' => max(1, min(3, (int) $slot->semester)),
                'category' => $slot->category ?: 'required',
                'credits' => (int) ($slot->credits ?? $slot->course?->credits ?? 0),
                'slotted' => true,
                'placed' => true,
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
     * Years → semesters → rows, mirroring the student study grid. The special
     * semester only appears when the plan actually uses one.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{years: array<int, array<int, array{rows: Collection<int, array<string, mixed>>, credits: int}>>, semesters: array<int, int>, maxYear: int}
     */
    private function gridFor(CoursePlan $plan, Collection $rows): array
    {
        $placed = $rows->where('placed', true);

        $maxYear = max(
            1,
            (int) ceil((float) ($plan->program?->duration_years ?? 4)),
            (int) ($placed->max('year') ?? 0),
        );

        $semesters = $placed->contains(fn (array $row) => $row['semester'] === 3)
            ? [1, 2, 3]
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
     * @return array{year: int, semester: int, category: string|null, credits: int|null, course_id: mixed}
     */
    private function validatedRow(Request $request): array
    {
        return $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'year' => ['required', 'integer', 'min:1', 'max:8'],
            'semester' => ['required', 'integer', 'in:1,2,3'],
            'category' => ['nullable', 'in:required,elective,university'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);
    }

    /**
     * The plan category a course belongs to, taken from its course type.
     */
    private function defaultCategory(Course $course): string
    {
        $type = mb_strtoupper((string) $course->courseType?->code);

        return match (true) {
            in_array($type, [CourseType::ELECTIVE_COURSE, CourseType::ELECTIVE_OPTION], true) => 'elective',
            $type === 'CT01' => 'university',
            default => 'required',
        };
    }

    private function placeholderLabel(PlanCourse $slot): string
    {
        $label = str_replace('_', ' ', (string) $slot->placeholder_key);

        return ucfirst(mb_strtolower(trim($label))) ?: 'Elective slot';
    }

    private function semesterLabel(int|string $semester): string
    {
        return self::SEMESTER_LABELS[(int) $semester] ?? 'Semester '.$semester;
    }
}
