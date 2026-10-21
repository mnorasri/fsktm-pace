<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\CoursePlan;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    use ImportsCsv;

    public function index(Request $request): View
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->with(['department', 'program', 'plan', 'advisor'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')))
            ->when($request->filled('advisor_id'), fn ($q) => $q->where('advisor_id', $request->integer('advisor_id')))
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
            'advisors' => User::where('role', User::ROLE_ADVISOR)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validated($request);
        $attributes['password'] = Hash::make($request->input('password') ?: 'password');

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

        foreach ($this->readCsv($request->file('file')) as $row) {
            // Documented structure: matric_num, student_name, student_email,
            // program_code, plan_code, lecturer_id, student_phone.
            $matric = $this->csvValue($row, 'matric_num', 0) ?? $this->csvValue($row, 'student_id', 0);
            $name = $this->csvValue($row, 'student_name', 1) ?? $this->csvValue($row, 'name', 1);
            $email = $this->csvValue($row, 'student_email', 2) ?? $this->csvValue($row, 'email', 2);
            $programCode = $this->csvValue($row, 'program_code', 3) ?? $this->csvValue($row, 'program', 3);
            $planCode = $this->csvValue($row, 'plan_code', 4) ?? $this->csvValue($row, 'plan', 4);
            $lecturerId = $this->csvValue($row, 'lecturer_id', 5) ?? $this->csvValue($row, 'advisor', 5);
            $phone = $this->csvValue($row, 'student_phone', 6) ?? $this->csvValue($row, 'phone', 6);

            if (blank($email) && blank($matric)) {
                continue;
            }

            // Accounts sign in with an email; derive one from the matric number
            // when the sheet omits it (e.g. "a210001@student.fsktm.test").
            $email = $email ?: Str::lower($matric).'@student.fsktm.test';

            $program = $programCode
                ? Program::whereRaw('lower(code) = ?', [mb_strtolower($programCode)])->with('plans')->first()
                : null;

            $plan = $planCode
                ? CoursePlan::whereRaw('lower(code) = ?', [mb_strtolower($planCode)])->first()
                : $program?->plans->first();

            // PAK mapping: the lecturer ID (staff ID) of the assigned advisor,
            // with the advisor email accepted as a fallback.
            $advisor = null;
            if ($lecturerId) {
                $advisor = User::where('role', User::ROLE_ADVISOR)
                    ->where(fn ($q) => $q->where('staff_id', $lecturerId)->orWhere('email', $lecturerId))
                    ->first();
            }

            $attributes = [
                'name' => $name ?? $email,
                'student_id' => $matric,
                'program_id' => $program?->id,
                'plan_id' => $plan?->id,
                'department_id' => $program?->department_id,
                'advisor_id' => $advisor?->id,
                'phone' => $phone,
                'role' => User::ROLE_STUDENT,
            ];

            // Match on the matric number first so re-imports update existing
            // students instead of creating duplicates.
            $existing = ($matric ? User::where('student_id', $matric)->first() : null)
                ?? User::where('email', $email)->first();

            if ($existing) {
                $existing->update($attributes);
                $updated++;
            } else {
                User::create($attributes + [
                    'email' => $email,
                    'password' => Hash::make('password'),
                ]);
                $created++;
            }
        }

        return back()->with('status', "Student import complete: {$created} created, {$updated} updated. Default password: password");
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
            'student_id' => ['nullable', 'string', 'max:30', 'unique:users,student_id'.($student ? ','.$student->id : '')],
            'program_id' => ['nullable', 'exists:programs,id'],
            'plan_id' => ['nullable', 'exists:course_plans,id'],
            'advisor_id' => ['nullable', 'exists:users,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
