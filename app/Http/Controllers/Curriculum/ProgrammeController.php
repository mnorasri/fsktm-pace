<?php

namespace App\Http\Controllers\Curriculum;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Programmes, shared by the administrator, Heads of Department and Heads of
 * Program. The programme code is the key: it is unique, used by every import
 * and by the study plans that point at the programme.
 */
class ProgrammeController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): View
    {
        $programs = Program::query()
            ->with('department')
            ->withCount(['plans', 'courses'])
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('curriculum.programmes', [
            'programs' => $programs,
            'departments' => Department::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Program::create($this->attributes($data));

        return back()->with('status', 'Programme '.$data['programme_code'].' created.');
    }

    public function update(Request $request, Program $program): RedirectResponse
    {
        $data = $this->validated($request, $program);

        $program->update($this->attributes($data));

        return back()->with('status', 'Programme '.$program->code.' updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $code = $program->code;
        $program->delete();

        return back()->with('status', 'Programme '.$code.' deleted.');
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

        $report = $this->importReport('Programme', $created, $updated, $failures);

        return back()
            ->with('status', "Programme import: {$created} created, {$updated} updated, {$report['failed']} failed.")
            ->with('import_report', $report);
    }

    /**
     * Import one row: programme_code, programme_name, department_code and,
     * optionally, duration_years and total_credits.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importRow(array $row): string
    {
        $code = $this->csvValue($row, 'programme_code', 0) ?? $this->csvValue($row, 'code', 0);

        if (blank($code)) {
            throw new RuntimeException('programme_code is required');
        }

        $name = $this->csvValue($row, 'programme_name', 1) ?? $this->csvValue($row, 'name', 1);

        if (blank($name)) {
            throw new RuntimeException('programme_name is required');
        }

        $departmentCode = $this->csvValue($row, 'department_code', 2);

        if (blank($departmentCode)) {
            throw new RuntimeException('department_code is required');
        }

        $department = Department::whereRaw('LOWER(code) = ?', [mb_strtolower($departmentCode)])->first();

        if (! $department) {
            throw new RuntimeException('department_code '.$departmentCode.' does not exist');
        }

        $attributes = [
            'name' => $name,
            'department_id' => $department->id,
        ];

        $duration = $this->csvValue($row, 'duration_years', 3);
        $credits = $this->csvValue($row, 'total_credits', 4);

        if (filled($duration)) {
            if (! is_numeric($duration) || (float) $duration <= 0) {
                throw new RuntimeException('duration_years must be a positive number');
            }

            $attributes['duration_years'] = (float) $duration;
        }

        if (filled($credits)) {
            if (! ctype_digit($credits)) {
                throw new RuntimeException('total_credits must be a whole number');
            }

            $attributes['total_credits'] = (int) $credits;
        }

        $existing = Program::whereRaw('LOWER(code) = ?', [mb_strtolower($code)])->first();

        if ($existing) {
            $existing->update($attributes);

            return 'updated';
        }

        Program::create(['code' => $code] + $attributes + [
            'duration_years' => 4,
            'total_credits' => 120,
        ]);

        return 'created';
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Program $program = null): array
    {
        return $request->validate([
            'programme_code' => ['required', 'string', 'max:30', 'unique:programs,code'.($program ? ','.$program->id : '')],
            'programme_name' => ['required', 'string', 'max:180'],
            'department_code' => ['required', 'string', 'exists:departments,code'],
            'duration_years' => ['nullable', 'numeric', 'min:1', 'max:8'],
            'total_credits' => ['nullable', 'integer', 'min:1', 'max:300'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(array $data): array
    {
        return array_filter([
            'code' => $data['programme_code'],
            'name' => $data['programme_name'],
            'department_id' => Department::where('code', $data['department_code'])->value('id'),
            'duration_years' => $data['duration_years'] ?? null,
            'total_credits' => $data['total_credits'] ?? null,
        ], fn ($value) => $value !== null);
    }
}
