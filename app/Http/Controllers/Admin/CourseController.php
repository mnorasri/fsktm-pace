<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\MqfSc;
use App\Models\Program;
use App\Models\Sdg;
use App\Models\Vbe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    use ImportsCsv;

    /**
     * Reference records auto-created during the last import, for the report.
     *
     * @var array<int, string>
     */
    protected array $newReferences = [];

    public function index(Request $request): View
    {
        $courses = Course::query()
            ->with(['program', 'coursePlan', 'courseType', 'mqfSc', 'vbe', 'sdg'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('course_type_id'), fn ($q) => $q->where('course_type_id', $request->integer('course_type_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner->where('code', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.courses', [
            'courses' => $courses,
            'programs' => Program::orderBy('code')->get(),
            'plans' => CoursePlan::orderBy('code')->get(),
            'courseTypes' => CourseType::orderBy('code')->get(),
            'mqfs' => MqfSc::orderBy('code')->get(),
            'vbes' => Vbe::orderBy('code')->get(),
            'sdgs' => Sdg::ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Course::create($this->validated($request));

        return back()->with('status', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $course->update($this->validated($request, $course));

        return back()->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return back()->with('status', 'Course deleted.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $created = 0;
        $updated = 0;
        $this->newReferences = [];

        foreach ($this->readCsv($request->file('file')) as $row) {
            $code = $this->csvValue($row, 'course_code', 0) ?? $this->csvValue($row, 'code', 0);

            if (blank($code)) {
                continue;
            }

            // Resolve each lookup by code. Unknown codes are matched loosely
            // (case-insensitive, then prefix) or created in Config so the
            // imported value is never silently dropped.
            $program = $this->resolveReference(Program::class, $this->csvValue($row, 'program_code', 6));
            $type = $this->resolveReference(CourseType::class, $this->csvValue($row, 'course_type', 2));
            $plan = $this->resolveReference(CoursePlan::class, $this->csvValue($row, 'plan_code', 7), ['program_id' => $program?->id]);
            $sc = $this->resolveReference(MqfSc::class, $this->csvValue($row, 'sc_code', 9) ?? $this->csvValue($row, 'sc', 9));
            $vbe = $this->resolveReference(Vbe::class, $this->csvValue($row, 'vbe_code', 10) ?? $this->csvValue($row, 'vbe', 10));
            $sdg = $this->resolveSdg($this->csvValue($row, 'sdg_code', 11) ?? $this->csvValue($row, 'sdg', 11));

            $attributes = [
                'name' => $this->csvValue($row, 'course_name', 1) ?? $this->csvValue($row, 'name', 1) ?? $code,
                'course_type_id' => $type?->id,
                'credits' => (int) ($this->csvValue($row, 'credits', 3) ?? 3),
                'course_load' => $this->csvValue($row, 'course_load', 4),
                'semester' => $this->csvValue($row, 'semester', 5),
                'program_id' => $program?->id,
                'course_plan_id' => $plan?->id,
                'prerequisites' => $this->csvValue($row, 'prerequisites', 8) ?? $this->csvValue($row, 'prerequisite', 8),
                'mqf_sc_id' => $sc?->id,
                'vbe_id' => $vbe?->id,
                'sdg_id' => $sdg?->id,
            ];

            // Optional column: an existing group is kept when the CSV omits it,
            // so older exports do not wipe the either/or setup.
            $alternativeGroup = $this->csvValue($row, 'alternative_group', 12);

            if (filled($alternativeGroup)) {
                $attributes['alternative_group'] = $alternativeGroup;
            }

            $existing = Course::where('code', $code)->first();

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                Course::create(['code' => $code] + $attributes);
                $created++;
            }
        }

        $message = "CSV import complete: {$created} created, {$updated} updated.";

        if ($this->newReferences !== []) {
            $message .= ' New config entries: '.implode(', ', $this->newReferences).'.';
        }

        return back()->with('status', $message);
    }

    /**
     * Resolve a Config reference by code: exact match (case-insensitive), then a
     * prefix match against a longer configured code, otherwise create the entry
     * so nothing is dropped.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $extra
     */
    protected function resolveReference(string $modelClass, ?string $value, array $extra = []): ?Model
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $needle = mb_strtolower($value);
        $all = $modelClass::all();

        $match = $all->first(fn (Model $model) => mb_strtolower($model->code) === $needle);

        if (! $match) {
            // The imported code is a prefix of a configured one, e.g.
            // BIM20252026-LOC → BIM20252026-LOCAL. Longest match wins.
            $match = $all
                ->filter(fn (Model $model) => str_starts_with(mb_strtolower($model->code), $needle))
                ->sortByDesc(fn (Model $model) => mb_strlen($model->code))
                ->first();
        }

        if ($match) {
            return $match;
        }

        $created = $modelClass::create(['code' => $value, 'name' => $value] + $extra);
        $this->newReferences[] = (new $modelClass)->getTable().':'.$value;

        return $created;
    }

    /**
     * Resolve a single UN SDG from a CSV value such as "SDG9", "9" or "sdg 9".
     */
    protected function resolveSdg(?string $value): ?Sdg
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:sdg)?\s*(\d{1,2})$/i', $value, $matches)) {
            $value = 'SDG'.$matches[1];
        }

        return $this->resolveReference(Sdg::class, $value);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:courses,code'.($course ? ','.$course->id : '')],
            'name' => ['required', 'string', 'max:180'],
            'course_type_id' => ['nullable', 'exists:course_types,id'],
            'credits' => ['required', 'integer', 'min:1', 'max:10'],
            'course_load' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:10'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'course_plan_id' => ['nullable', 'exists:course_plans,id'],
            'prerequisites' => ['nullable', 'string', 'max:255'],
            'mqf_sc_id' => ['nullable', 'exists:mqf_sc,id'],
            'vbe_id' => ['nullable', 'exists:vbe,id'],
            'sdg_id' => ['nullable', 'exists:sdgs,id'],
            'alternative_group' => ['nullable', 'string', 'max:60'],
        ]);
    }
}
