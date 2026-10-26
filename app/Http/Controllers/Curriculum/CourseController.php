<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\MqfSc;
use App\Models\Sdg;
use App\Models\Vbe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Courses, shared by the administrator, Heads of Department and Heads of
 * Program. The course code is the key, and the course type / SC / VBE EPT / SDG
 * references are stored as their codes.
 */
class CourseController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): View
    {
        $courses = Course::query()
            ->with(['courseType', 'mqfSc', 'vbe', 'sdg'])
            ->when($request->filled('course_type_code'), fn ($q) => $q->where('course_type_code', $request->string('course_type_code')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('course_code', 'like', $term)
                    ->orWhere('course_name_en', 'like', $term)
                    ->orWhere('course_name_my', 'like', $term));
            })
            ->orderBy('course_code')
            ->paginate(15)
            ->withQueryString();

        return view('curriculum.courses', [
            'courses' => $courses,
            'courseTypes' => CourseType::orderBy('code')->get(),
            'mqfs' => MqfSc::orderBy('code')->get(),
            'vbes' => Vbe::orderBy('code')->get(),
            'sdgs' => Sdg::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $attributes = $this->attributes($data);

        // A course entered by a department manager belongs to that department.
        if ($departmentId = auth()->user()->department_id) {
            $attributes['department_id'] = $departmentId;
        }

        Course::create($attributes);

        return back()->with('status', 'Course '.$data['course_code'].' created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $this->validated($request, $course);

        $course->update($this->attributes($data));

        return back()->with('status', 'Course '.$course->course_code.' updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $code = $course->course_code;
        $course->delete();

        return back()->with('status', 'Course '.$code.' deleted.');
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

        $report = $this->importReport('Course', $created, $updated, $failures);

        return back()
            ->with('status', "Course import: {$created} created, {$updated} updated, {$report['failed']} failed.")
            ->with('import_report', $report);
    }

    /**
     * Import one row: course_code, course_name_en, course_name_my, credit_hours,
     * course_type_code, course_load, pre_requisite, sc_code, vbe_ept_code,
     * sdg_code.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importRow(array $row): string
    {
        $code = $this->csvValue($row, 'course_code', 0) ?? $this->csvValue($row, 'code', 0);

        if (blank($code)) {
            throw new RuntimeException('course_code is required');
        }

        $name = $this->csvValue($row, 'course_name_en', 1)
            ?? $this->csvValue($row, 'course_name', 1)
            ?? $this->csvValue($row, 'name', 1);

        if (blank($name)) {
            throw new RuntimeException('course_name_en is required');
        }

        $credits = $this->csvValue($row, 'credit_hours', 3)
            ?? $this->csvValue($row, 'credits', 3)
            ?? $this->csvValue($row, 'credit_hour', 3)
            ?? '3';

        if (! ctype_digit($credits) || (int) $credits < 1 || (int) $credits > 20) {
            throw new RuntimeException('credit_hours must be a whole number between 1 and 20');
        }

        $load = $this->csvValue($row, 'course_load', 5);

        if (filled($load) && ! is_numeric($load)) {
            throw new RuntimeException('course_load must be a number');
        }

        $attributes = [
            'course_name_en' => $name,
            'course_name_my' => $this->csvValue($row, 'course_name_my', 2) ?? $this->csvValue($row, 'name_my', 2),
            'credit_hours' => (int) $credits,
            'course_load' => filled($load) ? (float) $load : null,
            'course_type_code' => $this->reference(CourseType::class, $this->csvValue($row, 'course_type_code', 4) ?? $this->csvValue($row, 'course_type', 4), 'course_type_code'),
            'pre_requisite' => $this->csvValue($row, 'pre_requisite', 6) ?? $this->csvValue($row, 'prerequisites', 6),
            'sc_code' => $this->reference(MqfSc::class, $this->csvValue($row, 'sc_code', 7), 'sc_code'),
            'vbe_ept_code' => $this->reference(Vbe::class, $this->csvValue($row, 'vbe_ept_code', 8) ?? $this->csvValue($row, 'vbe_code', 8) ?? $this->csvValue($row, 'vbe', 8), 'vbe_ept_code'),
            'sdg_code' => $this->reference(Sdg::class, $this->csvValue($row, 'sdg_code', 9) ?? $this->csvValue($row, 'sdg', 9), 'sdg_code'),
        ];

        $existing = Course::whereRaw('LOWER(course_code) = ?', [mb_strtolower($code)])->first();

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        $attributes['course_code'] = $code;
        $attributes['is_active'] = true;

        if ($departmentId = auth()->user()->department_id) {
            $attributes['department_id'] = $departmentId;
        }

        Course::create($attributes);

        return 'created';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'course_code' => ['required', 'string', 'max:30', 'unique:courses,course_code'.($course ? ','.$course->id : '')],
            'course_name_en' => ['required', 'string', 'max:180'],
            'course_name_my' => ['nullable', 'string', 'max:180'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:20'],
            'course_type_code' => ['nullable', 'string', 'exists:course_types,code'],
            'course_load' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'pre_requisite' => ['nullable', 'string', 'max:1000'],
            'sc_code' => ['nullable', 'string', 'exists:mqf_sc,code'],
            'vbe_ept_code' => ['nullable', 'string', 'exists:vbe,code'],
            'sdg_code' => ['nullable', 'string', 'exists:sdgs,code'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return [
            'course_code' => $data['course_code'],
            'course_name_en' => $data['course_name_en'],
            'course_name_my' => $data['course_name_my'] ?? null,
            'credit_hours' => $data['credit_hours'],
            'course_type_code' => $this->codeFor(CourseType::class, $data['course_type_code'] ?? null),
            'course_load' => $data['course_load'] ?? null,
            'pre_requisite' => $data['pre_requisite'] ?? null,
            'sc_code' => $this->codeFor(MqfSc::class, $data['sc_code'] ?? null),
            'vbe_ept_code' => $this->codeFor(Vbe::class, $data['vbe_ept_code'] ?? null),
            'sdg_code' => $this->codeFor(Sdg::class, $data['sdg_code'] ?? null),
        ];
    }

    /**
     * Return the configured code exactly as stored (case-insensitive match).
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function codeFor(string $modelClass, ?string $code): ?string
    {
        if (blank($code)) {
            return null;
        }

        return $modelClass::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->value('code');
    }

    /**
     * Resolve a by-code reference, failing the import row when the code does
     * not exist.
     *
     * @param  class-string<Model>  $modelClass
     *
     * @throws RuntimeException
     */
    protected function reference(string $modelClass, ?string $code, string $field): ?string
    {
        if (blank($code)) {
            return null;
        }

        $resolved = $this->codeFor($modelClass, $code);

        if ($resolved === null) {
            throw new RuntimeException($field.' '.$code.' does not exist');
        }

        return $resolved;
    }
}
