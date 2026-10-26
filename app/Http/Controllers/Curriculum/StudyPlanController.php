<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\CoursePlan;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Study plans, shared by the administrator, Heads of Department and Heads of
 * Program. The plan code is the key; a plan belongs to one programme and its
 * course rows live in Study Plan Details.
 */
class StudyPlanController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): View
    {
        $plans = CoursePlan::query()
            ->with('program.department')
            ->withCount(['planCourses', 'students'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('curriculum.study-plans', [
            'plans' => $plans,
            'programs' => Program::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        CoursePlan::create($this->attributes($data));

        return back()->with('status', 'Study plan '.$data['study_plan_code'].' created.');
    }

    public function update(Request $request, CoursePlan $studyPlan): RedirectResponse
    {
        $data = $this->validated($request, $studyPlan);

        $studyPlan->update($this->attributes($data));

        return back()->with('status', 'Study plan '.$studyPlan->code.' updated.');
    }

    public function destroy(CoursePlan $studyPlan): RedirectResponse
    {
        $code = $studyPlan->code;
        $studyPlan->delete();

        return back()->with('status', 'Study plan '.$code.' deleted.');
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

        $report = $this->importReport('Study plan', $created, $updated, $failures);

        return back()
            ->with('status', "Study plan import: {$created} created, {$updated} updated, {$report['failed']} failed.")
            ->with('import_report', $report);
    }

    /**
     * Import one row: study_plan_code, study_plan_name, programme_code,
     * version, total_semester.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importRow(array $row): string
    {
        $code = $this->csvValue($row, 'study_plan_code', 0) ?? $this->csvValue($row, 'code', 0);

        if (blank($code)) {
            throw new RuntimeException('study_plan_code is required');
        }

        $name = $this->csvValue($row, 'study_plan_name', 1) ?? $this->csvValue($row, 'name', 1);

        if (blank($name)) {
            throw new RuntimeException('study_plan_name is required');
        }

        $programmeCode = $this->csvValue($row, 'programme_code', 2);

        if (blank($programmeCode)) {
            throw new RuntimeException('programme_code is required');
        }

        $programId = Program::whereRaw('LOWER(code) = ?', [mb_strtolower($programmeCode)])->value('id');

        if ($programId === null) {
            throw new RuntimeException('programme_code '.$programmeCode.' does not exist');
        }

        $attributes = [
            'name' => $name,
            'program_id' => $programId,
            'version' => $this->csvValue($row, 'version', 3),
            'total_semester' => null,
        ];

        $semesters = $this->csvValue($row, 'total_semester', 4);

        if (filled($semesters)) {
            if (! ctype_digit($semesters) || (int) $semesters < 1 || (int) $semesters > 16) {
                throw new RuntimeException('total_semester must be a whole number between 1 and 16');
            }

            $attributes['total_semester'] = (int) $semesters;
        }

        $existing = CoursePlan::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        CoursePlan::create(['code' => $code] + $attributes);

        return 'created';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?CoursePlan $plan = null): array
    {
        return $request->validate([
            'study_plan_code' => ['required', 'string', 'max:60', 'unique:course_plans,code'.($plan ? ','.$plan->id : '')],
            'study_plan_name' => ['required', 'string', 'max:180'],
            'programme_code' => ['required', 'string', 'exists:programs,code'],
            'version' => ['nullable', 'string', 'max:40'],
            'total_semester' => ['nullable', 'integer', 'min:1', 'max:16'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return [
            'code' => $data['study_plan_code'],
            'name' => $data['study_plan_name'],
            'program_id' => Program::where('code', $data['programme_code'])->value('id'),
            'version' => $data['version'] ?? null,
            'total_semester' => $data['total_semester'] ?? null,
        ];
    }
}
