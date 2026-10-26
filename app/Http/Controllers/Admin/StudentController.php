<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\CoursePlan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class StudentController extends Controller
{
    use ImportsCsv;

    /**
     * The password every account starts with when none is supplied.
     */
    public const DEFAULT_PASSWORD = 'R@hsi4';

    public function index(Request $request): View
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['department', 'program', 'plan', 'advisor'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('advisor_id'), fn ($q) => $q->where('advisor_id', $request->input('advisor_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('student_id', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students', [
            'students' => $students,
            'programs' => Program::with('plans')->orderBy('code')->get(),
            'plans' => CoursePlan::orderBy('code')->get(),
            'advisors' => User::where('role', User::ROLE_ADVISOR)->whereNotNull('staff_id')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validated($request);
        $attributes['password'] = Hash::make($request->input('password') ?: self::DEFAULT_PASSWORD);

        $student = User::create($attributes + ['role' => User::ROLE_STUDENT]);
        $this->syncDepartment($student);

        return back()->with('status', 'Student account created.');
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);

        $attributes = $this->validated($request, $student);

        if (filled($request->input('password'))) {
            $attributes['password'] = Hash::make($request->input('password'));
        }

        $student->update($attributes);
        $this->syncDepartment($student);

        return back()->with('status', 'Student account updated.');
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);

        $student->delete();

        return back()->with('status', 'Student account removed.');
    }

    public function resetPassword(User $student): RedirectResponse
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);

        $password = Str::password(10);
        $student->update(['password' => Hash::make($password)]);

        return back()
            ->with('status', 'Password reset for '.$student->name.'.')
            ->with('reset_password', ['name' => $student->name, 'password' => $password]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $created = 0;
        $updated = 0;
        $failures = [];

        // A bad row must never stop the import: each row is handled on its own
        // and its failure is recorded so the summary can report it.
        foreach ($this->readCsv($request->file('file')) as $index => $row) {
            try {
                $result = $this->importStudentRow($row);

                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (Throwable $e) {
                $failures[] = 'Line '.($index + 2).': '.$this->failureReason($e);
            }
        }

        $report = $this->importReport('Student', $created, $updated, $failures);

        return back()
            ->with('status', "Student import: {$created} created, {$updated} updated, {$report['failed']} failed. Default password: ".self::DEFAULT_PASSWORD.'.')
            ->with('import_report', $report);
    }

    /**
     * Import one CSV row, returning whether the student was created or updated.
     * Data problems raise an exception the caller records without aborting the
     * rest of the file.
     *
     * @param  array<string|int, string>  $row
     *
     * @throws RuntimeException
     */
    protected function importStudentRow(array $row): string
    {
        // Documented structure: matric_num, student_name, student_email,
        // program_code, plan_code, advisor_id, password. Older sheets that
        // still name the advisor column lecturer_id keep importing.
        $matric = $this->csvValue($row, 'matric_num', 0) ?? $this->csvValue($row, 'student_id', 0);
        $name = $this->csvValue($row, 'student_name', 1) ?? $this->csvValue($row, 'name', 1);
        $email = $this->csvValue($row, 'student_email', 2) ?? $this->csvValue($row, 'email', 2);
        $programCode = $this->csvValue($row, 'program_code', 3) ?? $this->csvValue($row, 'program', 3);
        $planCode = $this->csvValue($row, 'plan_code', 4) ?? $this->csvValue($row, 'plan', 4);
        $advisorKey = $this->csvValue($row, 'advisor_id', 5) ?? $this->csvValue($row, 'lecturer_id', 5);
        $password = $this->csvValue($row, 'password', 6);

        // A matric number is the student's key; a row without one cannot be
        // matched safely.
        if (blank($matric)) {
            throw new RuntimeException('matric_num is required');
        }

        // Accounts sign in with an email; derive one from the matric number
        // when the sheet omits it (e.g. "a210001@student.fsktm.test").
        $emailProvided = filled($email);
        $email = $email ?: Str::lower($matric).'@student.fsktm.test';

        $program = $programCode
            ? Program::whereRaw('lower(code) = ?', [mb_strtolower($programCode)])->with('plans')->first()
            : null;

        if ($programCode && ! $program) {
            throw new RuntimeException("program_code \"{$programCode}\" was not found");
        }

        $plan = $planCode
            ? CoursePlan::whereRaw('lower(code) = ?', [mb_strtolower($planCode)])->first()
            : $program?->plans->first();

        if ($planCode && ! $plan) {
            throw new RuntimeException("plan_code \"{$planCode}\" was not found");
        }

        // PAK mapping: the foreign key is the advisor's staff ID, with the
        // advisor email accepted as a fallback for older sheets.
        $advisor = null;
        if ($advisorKey) {
            $advisor = User::where('role', User::ROLE_ADVISOR)
                ->where(fn ($q) => $q->where('staff_id', $advisorKey)->orWhere('email', $advisorKey))
                ->first();

            if (! $advisor) {
                throw new RuntimeException("advisor_id \"{$advisorKey}\" does not match any lecturer's staff ID");
            }
        }

        $attributes = [
            'name' => $name ?? $email,
            'student_id' => $matric,
            'program_id' => $program?->id,
            'plan_id' => $plan?->id,
            'department_id' => $program?->department_id,
            'advisor_id' => $advisor?->staff_id,
            'role' => User::ROLE_STUDENT,
        ];

        // Every row commits on its own, so a failure leaves no partial student.
        return DB::transaction(function () use ($attributes, $matric, $email, $emailProvided, $password) {
            // Match on the matric number first so re-imports update existing
            // students instead of creating duplicates.
            $existing = User::where('student_id', $matric)->first()
                ?? User::where('email', $email)->first();

            if ($existing && $existing->role !== User::ROLE_STUDENT) {
                throw new RuntimeException("email \"{$email}\" belongs to an existing {$existing->role} account");
            }

            if ($existing) {
                // Keep the login address in sync when the sheet supplies a new
                // one, unless another account already owns it.
                if ($emailProvided && ! User::where('email', $email)->where('id', '!=', $existing->id)->exists()) {
                    $attributes['email'] = $email;
                }

                $existing->update($attributes);

                // A blank password leaves the current one untouched.
                if (filled($password)) {
                    $existing->update(['password' => $this->passwordFrom($password)]);
                }

                return 'updated';
            }

            User::create($attributes + [
                'email' => $email,
                'password' => $this->passwordFrom($password),
            ]);

            return 'created';
        });
    }

    /**
     * A password cell may carry a plain password or an existing hash — hashing
     * a hash would lock the account out, so a usable hash is stored untouched.
     * Blank cells fall back to the default.
     */
    protected function passwordFrom(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return Hash::make(self::DEFAULT_PASSWORD);
        }

        return Hash::isHashed($value) ? $value : Hash::make($value);
    }

    protected function syncDepartment(User $student): void
    {
        if ($student->program && $student->program->department_id !== $student->department_id) {
            $student->update(['department_id' => $student->program->department_id]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $student = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'.($student ? ','.$student->id : '')],
            'student_id' => ['required', 'string', 'max:30', 'unique:users,student_id'.($student ? ','.$student->id : '')],
            'program_id' => ['nullable', 'exists:programs,id'],
            'plan_id' => ['nullable', 'exists:course_plans,id'],
            'advisor_id' => ['nullable', 'exists:users,staff_id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
