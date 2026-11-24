<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\CoursePlan;
use App\Models\Program;
use App\Models\User;
use App\Support\DefaultPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class StudentController extends Controller
{
    use ImportsCsv;

    /**
     * The password every account starts with when none is supplied.
     */
    public const DEFAULT_PASSWORD = DefaultPasswords::CURRENT;

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
            ->orderBy(Program::select('code')->whereColumn('programs.id', 'users.program_id'))
            ->orderBy(CoursePlan::select('code')->whereColumn('course_plans.id', 'users.plan_id'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students', [
            'studentRoutePrefix' => $request->routeIs('advisor.students.*') ? 'advisor.students.' : 'admin.students.',
            'students' => $students,
            'programs' => Program::with('plans')->orderBy('code')->get(),
            'plans' => CoursePlan::orderBy('code')->get(),
            'advisors' => User::where('role', User::ROLE_ADVISOR)->whereNotNull('staff_id')->orderBy('name')->get(),
        ]);
    }

    /**
     * Browse students grouped by their PAK (academic) advisor, without changing
     * any table structure: each row lets the administrator re-place a student
     * on a programme and study plan from the same screen.
     */
    public function byAdvisor(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $selected = $request->input('advisor');

        $advisors = User::query()
            ->where('role', User::ROLE_ADVISOR)
            ->whereNotNull('staff_id')
            ->withCount(['advisees' => fn ($q) => $q->where('role', User::ROLE_STUDENT)])
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('staff_id', 'like', $term));
            })
            ->orderBy('name')
            ->get();

        $selectedAdvisor = null;
        $showUnassigned = $selected === 'none';
        $students = collect();

        if ($showUnassigned) {
            $students = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereNull('advisor_id')
                ->with(['program', 'plan'])
                ->orderBy(Program::select('code')->whereColumn('programs.id', 'users.program_id'))
                ->orderBy(CoursePlan::select('code')->whereColumn('course_plans.id', 'users.plan_id'))
                ->orderBy('name')
                ->orderBy('id')
                ->get();
        } elseif (filled($selected)) {
            $selectedAdvisor = User::query()
                ->where('role', User::ROLE_ADVISOR)
                ->whereKey($selected)
                ->firstOrFail();

            $students = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where('advisor_id', $selectedAdvisor->staff_id)
                ->with(['program', 'plan'])
                ->orderBy(Program::select('code')->whereColumn('programs.id', 'users.program_id'))
                ->orderBy(CoursePlan::select('code')->whereColumn('course_plans.id', 'users.plan_id'))
                ->orderBy('name')
                ->orderBy('id')
                ->get();
        }

        return view('admin.students-by-advisor', [
            'studentRoutePrefix' => $request->routeIs('advisor.students.*') ? 'advisor.students.' : 'admin.students.',
            'advisors' => $advisors,
            'selectedAdvisor' => $selectedAdvisor,
            'studentAdvisors' => ($selectedAdvisor || $showUnassigned)
                ? User::where('role', User::ROLE_ADVISOR)->whereNotNull('staff_id')->orderBy('name')->get()
                : collect(),
            'showUnassigned' => $showUnassigned,
            'students' => $students,
            'search' => $search,
            'unassigned' => User::where('role', User::ROLE_STUDENT)->whereNull('advisor_id')->count(),
            'totalStudents' => User::where('role', User::ROLE_STUDENT)->count(),
            'programs' => Program::with(['plans' => fn ($q) => $q->orderBy('code')])
                ->orderBy('code')
                ->get(),
        ]);
    }

    /**
     * Change one student's programme / study plan from the PAK advisor view.
     * The programme and plan stay consistent, and the department follows the
     * programme so the rest of the portal keeps working.
     */
    public function updatePlacement(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);

        $data = $request->validate([
            'program_id' => ['nullable', 'exists:programs,id'],
            'plan_id' => ['nullable', 'exists:course_plans,id'],
        ]);

        $plan = filled($data['plan_id'] ?? null)
            ? CoursePlan::find($data['plan_id'])
            : null;

        if ($plan && filled($data['program_id'] ?? null) && (int) $plan->program_id !== (int) $data['program_id']) {
            throw ValidationException::withMessages([
                'plan_id' => 'That study plan does not belong to the selected programme.',
            ]);
        }

        // A plan on its own implies its programme.
        if ($plan && blank($data['program_id'] ?? null)) {
            $data['program_id'] = $plan->program_id;
        }

        // Moving to another programme drops a plan that belongs to the old one,
        // so a student is never left on a programme/plan mismatch.
        if (! $plan && filled($data['program_id'] ?? null)) {
            $student->loadMissing('plan');

            if ($student->plan && (int) $student->plan->program_id !== (int) $data['program_id']) {
                $data['plan_id'] = null;
            }
        }

        $student->update($data);
        $this->syncDepartment($student);
        $student->refresh()->load(['program', 'plan']);

        return back()->with(
            'status',
            $student->name.' is now on '
                .($student->program?->code ?? 'no programme')
                .' / '.($student->plan?->code ?? 'no plan').'.'
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validated($request);
        $password = $request->input('password');

        $attributes['password'] = Hash::make($password ?: self::DEFAULT_PASSWORD);
        // A brand-new account always picks its own password at first sign-in,
        // whether it started with the shared default or one chosen by admin.
        $attributes['must_change_password'] = true;

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
            // A password supplied by the administrator is a temporary one:
            // the account replaces it at the next sign-in.
            $attributes['must_change_password'] = true;
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
        $student->update([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

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
            ? Program::whereRaw('lower(code) = ?', [mb_strtolower($programCode)])->first()
            : null;

        if ($programCode && ! $program) {
            throw new RuntimeException("program_code \"{$programCode}\" was not found");
        }

        $plan = $planCode
            ? CoursePlan::whereRaw('lower(code) = ?', [mb_strtolower($planCode)])->first()
            : null;

        if ($planCode && ! $plan) {
            throw new RuntimeException("plan_code \"{$planCode}\" was not found");
        }

        // The plan has to fit the programme it is imported with; otherwise the
        // student would sit on a plan from a different programme.
        if ($plan && $program && (int) $plan->program_id !== (int) $program->id) {
            throw new RuntimeException("plan_code \"{$planCode}\" does not belong to program_code \"{$programCode}\"");
        }

        // A plan on its own implies its programme, exactly like the admin
        // placement screen.
        if ($plan && ! $program) {
            $program = $plan->program;
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
            'role' => User::ROLE_STUDENT,
        ];

        // Only the cells the sheet actually carries are written. Re-importing a
        // roster without programme, plan or advisor columns therefore leaves an
        // existing placement alone instead of wiping it.
        $placement = [];

        if ($program) {
            $placement['program_id'] = $program->id;
            $placement['department_id'] = $program->department_id;
        }

        if ($plan) {
            $placement['plan_id'] = $plan->id;
        }

        if ($advisor) {
            $placement['advisor_id'] = $advisor->staff_id;
        }

        $programId = $program?->id;

        // Every imported account has to choose its own password at first
        // sign-in; a password in the sheet is only a temporary credential.
        $mustChange = true;

        // Every row commits on its own, so a failure leaves no partial student.
        return DB::transaction(function () use ($attributes, $placement, $matric, $email, $emailProvided, $password, $mustChange, $programId) {
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

                // Moving the student to another programme drops a plan that
                // belongs to the old one, unless the sheet names a plan.
                if ($programId
                    && ! array_key_exists('plan_id', $placement)
                    && $existing->plan
                    && (int) $existing->plan->program_id !== (int) $programId) {
                    $placement['plan_id'] = null;
                }

                $existing->update($attributes + $placement);

                // A blank password leaves the current one untouched.
                if (filled($password)) {
                    $existing->update([
                        'password' => $this->passwordFrom($password),
                        'must_change_password' => $mustChange,
                    ]);
                }

                return 'updated';
            }

            User::create($attributes + $placement + [
                'email' => $email,
                'password' => $this->passwordFrom($password),
                'must_change_password' => $mustChange,
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
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($student)],
            'student_id' => ['required', 'string', 'max:30', Rule::unique('users', 'student_id')->ignore($student)],
            'program_id' => ['nullable', 'exists:programs,id'],
            'plan_id' => ['nullable', 'exists:course_plans,id'],
            'advisor_id' => ['nullable', 'exists:users,staff_id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
