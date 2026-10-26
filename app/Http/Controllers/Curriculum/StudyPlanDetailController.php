<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\PlanCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Study Plan Details: the course rows of a study plan, one curriculum semester
 * per row. Shared by the administrator, Heads of Department and Heads of
 * Program.
 */
class StudyPlanDetailController extends Controller
{
    use ImportsCsv;

    /**
     * The longest curriculum the application's study grid can show (8 years).
     */
    private const MAX_SEMESTER = 16;

    public function index(Request $request): View
    {
        $programIds = auth()->user()->curriculumProgramIds();

        $details = PlanCourse::query()
            ->with(['plan.program', 'course.courseType'])
            ->when($programIds !== null, fn ($q) => $q->whereHas('plan', fn ($inner) => $inner->whereIn('program_id', $programIds)))
            ->when($request->filled('plan'), function ($q) use ($request) {
                $q->whereHas('plan', fn ($inner) => $inner->where('code', $request->string('plan')));
            })
            ->when($request->filled('semester'), fn ($q) => $q->where('semester', $request->integer('semester')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';

                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('course', fn ($c) => $c
                        ->where('course_code', 'like', $term)
                        ->orWhere('course_name_en', 'like', $term))
                        ->orWhereHas('plan', fn ($p) => $p->where('code', 'like', $term));
                });
            })
            ->orderBy('course_plan_id')
            ->orderBy('year')
            ->orderBy('semester')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        $plans = CoursePlan::query()
            ->with('program')
            ->when($programIds !== null, fn ($q) => $q->whereIn('program_id', $programIds))
            ->orderBy('code')
            ->get();

        $courses = Course::query()
            ->when($programIds !== null, fn ($q) => $q->whereIn('program_id', $programIds))
            ->orderBy('course_code')
            ->get(['id', 'course_code', 'course_name_en', 'credit_hours']);

        return view('curriculum.study-plan-details', [
            'details' => $details,
            'plans' => $plans,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$plan, $course, $semester] = $this->validated($request);

        $this->authorizePlanCoursePlan($plan);

        PlanCourse::create($this->attributes($plan, $course, $semester));

        return back()->with('status', $course->code.' added to '.$plan->code.' in semester '.$semester.'.');
    }

    public function update(Request $request, PlanCourse $detail): RedirectResponse
    {
        $this->authorizePlanCourse($detail);

        [$plan, $course, $semester] = $this->validated($request, $detail);

        $detail->update($this->attributes($plan, $course, $semester));

        return back()->with('status', 'Study plan row updated: '.$course->code.' in '.$plan->code.', semester '.$semester.'.');
    }

    public function destroy(PlanCourse $detail): RedirectResponse
    {
        $this->authorizePlanCourse($detail);

        $label = $detail->course?->code ?? $detail->placeholder_key ?? 'Study plan row';

        $detail->delete();

        return back()->with('status', $label.' removed from the study plan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $created = 0;
        $updated = 0;

        /** @var array<int, string> $failures */
        $failures = [];

        foreach ($this->readCsv($request->file('file')) as $index => $row) {
            try {
                $result = DB::transaction(fn () => $this->importRow($row));

                $result === 'created' ? $created++ : $updated++;
            } catch (Throwable $e) {
                $failures[] = 'Line '.($index + 2).': '.$this->failureReason($e);
            }
        }

        $report = $this->importReport('Study plan detail', $created, $updated, $failures);

        return back()
            ->with('status', "Study plan detail import: {$created} created, {$updated} updated, {$report['failed']} failed.")
            ->with('import_report', $report);
    }

    /**
     * Import one row: study_plan_code, course_code, semester.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importRow(array $row): string
    {
        $planCode = $this->csvValue($row, 'study_plan_code', 0);

        if (blank($planCode)) {
            throw new RuntimeException('study_plan_code is required');
        }

        $courseCode = $this->csvValue($row, 'course_code', 1);

        if (blank($courseCode)) {
            throw new RuntimeException('course_code is required');
        }

        $semester = $this->csvValue($row, 'semester', 2);

        if (blank($semester) || ! ctype_digit($semester)) {
            throw new RuntimeException('semester must be a whole number');
        }

        $semester = (int) $semester;

        if ($semester < 1 || $semester > self::MAX_SEMESTER) {
            throw new RuntimeException('semester must be between 1 and '.self::MAX_SEMESTER);
        }

        $plan = CoursePlan::whereRaw('LOWER(code) = ?', [mb_strtolower($planCode)])->first();

        if (! $plan) {
            throw new RuntimeException('study_plan_code '.$planCode.' does not exist');
        }

        $course = Course::whereRaw('LOWER(course_code) = ?', [mb_strtolower($courseCode)])->first();

        if (! $course) {
            throw new RuntimeException('course_code '.$courseCode.' does not exist');
        }

        if ($plan->total_semester && $semester > $plan->total_semester) {
            throw new RuntimeException('semester '.$semester.' exceeds '.$plan->code.'\'s '.$plan->total_semester.' semesters');
        }

        $existing = PlanCourse::where('course_plan_id', $plan->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            $existing->update($this->attributes($plan, $course, $semester));

            return 'updated';
        }

        PlanCourse::create($this->attributes($plan, $course, $semester));

        return 'created';
    }

    /**
     * @return array{0: CoursePlan, 1: Course, 2: int}
     *
     * @throws ValidationException
     */
    protected function validated(Request $request, ?PlanCourse $detail = null): array
    {
        $data = $request->validate([
            'study_plan_code' => ['required', 'string', 'exists:course_plans,code'],
            'course_code' => ['required', 'string', 'exists:courses,course_code'],
            'semester' => ['required', 'integer', 'min:1', 'max:'.self::MAX_SEMESTER],
        ]);

        $plan = CoursePlan::where('code', $data['study_plan_code'])->firstOrFail();
        $course = Course::where('course_code', $data['course_code'])->firstOrFail();
        $semester = (int) $data['semester'];

        if ($plan->total_semester && $semester > $plan->total_semester) {
            throw ValidationException::withMessages([
                'semester' => 'Semester cannot exceed the plan\'s '.$plan->total_semester.' semesters.',
            ]);
        }

        $duplicate = PlanCourse::query()
            ->where('course_plan_id', $plan->id)
            ->where('course_id', $course->id)
            ->when($detail, fn ($q) => $q->whereKeyNot($detail->getKey()))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'course_code' => $course->code.' is already in '.$plan->code.'. Edit that row instead.',
            ]);
        }

        return [$plan, $course, $semester];
    }

    /**
     * The stored row: a curriculum semester maps to a year and an odd/even term
     * so the rest of the application (the study grid, pre-registration and the
     * student plan) keeps working unchanged.
     *
     * @return array<string, mixed>
     */
    protected function attributes(CoursePlan $plan, Course $course, int $semester): array
    {
        return [
            'course_plan_id' => $plan->id,
            'course_id' => $course->id,
            'year' => (int) ceil($semester / 2),
            'semester' => $semester % 2 === 1 ? 1 : 2,
            'category' => $this->category($course),
            'credits' => $course->credit_hours,
        ];
    }

    protected function category(Course $course): string
    {
        $type = mb_strtoupper((string) $course->courseType?->code);

        return match (true) {
            in_array($type, [CourseType::ELECTIVE_COURSE, CourseType::ELECTIVE_OPTION], true) => 'elective',
            $type === 'CT01' => 'university',
            default => 'required',
        };
    }

    protected function authorizePlanCourse(PlanCourse $detail): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isHod()) {
            return;
        }

        abort_unless(
            $user->isHeadOfProgram() && $detail->plan && in_array($detail->plan->program_id, $user->curriculumProgramIds() ?? [], true),
            403,
            'You may only manage study plan rows for programmes you head.'
        );
    }

    protected function authorizePlanCoursePlan(CoursePlan $plan): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->isHod()) {
            return;
        }

        abort_unless(
            $user->isHeadOfProgram() && in_array($plan->program_id, $user->curriculumProgramIds() ?? [], true),
            403,
            'You may only manage study plan rows for programmes you head.'
        );
    }
}
