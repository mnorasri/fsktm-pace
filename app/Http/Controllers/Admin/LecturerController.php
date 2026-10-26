<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class LecturerController extends Controller
{
    use ImportsCsv;

    /**
     * The password every account starts with when none is supplied.
     */
    public const DEFAULT_PASSWORD = 'R@hsi4';

    public function index(Request $request): View
    {
        $lecturers = User::query()
            ->where('role', User::ROLE_ADVISOR)
            ->with(['department', 'advisees'])
            ->withCount('advisees')
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('staff_id', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.lecturers', [
            'lecturers' => $lecturers,
            'departments' => Department::orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request) + [
            'role' => User::ROLE_ADVISOR,
            'password' => $this->passwordFrom($request->input('password')),
        ]);

        return back()->with('status', 'Lecturer account created. Default password: '.self::DEFAULT_PASSWORD.'.');
    }

    public function update(Request $request, User $lecturer): RedirectResponse
    {
        abort_unless($lecturer->role === User::ROLE_ADVISOR, 404);

        $lecturer->updateWithStaffId($this->validated($request, $lecturer));

        return back()->with('status', 'Lecturer account updated.');
    }

    public function destroy(User $lecturer): RedirectResponse
    {
        abort_unless($lecturer->role === User::ROLE_ADVISOR, 404);

        // Students reference the lecturer's staff ID; clear their advisor.
        User::where('advisor_id', $lecturer->staff_id)->update(['advisor_id' => null]);

        $lecturer->delete();

        return back()->with('status', 'Lecturer account removed.');
    }

    public function resetPassword(User $lecturer): RedirectResponse
    {
        abort_unless($lecturer->role === User::ROLE_ADVISOR, 404);

        $password = Str::password(10);
        $lecturer->update(['password' => Hash::make($password)]);

        return back()
            ->with('status', 'Password reset for '.$lecturer->name.'.')
            ->with('reset_password', ['name' => $lecturer->name, 'password' => $password]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $created = 0;
        $updated = 0;
        $hashed = 0;
        $failures = [];

        // A bad row must never stop the import: each row is handled on its own
        // and its failure is recorded so the summary can report it.
        foreach ($this->readCsv($request->file('file')) as $index => $row) {
            try {
                $result = $this->importLecturerRow($row);

                if ($result['status'] === 'created') {
                    $created++;
                } else {
                    $updated++;
                }

                if ($result['hashed']) {
                    $hashed++;
                }
            } catch (Throwable $e) {
                $failures[] = 'Line '.($index + 2).': '.$this->failureReason($e);
            }
        }

        $report = $this->importReport('Lecturer', $created, $updated, $failures);

        $status = "Lecturer import: {$created} created, {$updated} updated, {$report['failed']} failed. "
            .'Default password: '.self::DEFAULT_PASSWORD.'.';

        if ($hashed > 0) {
            $status .= " {$hashed} row(s) supplied an already-hashed password and were kept as-is.";
        }

        return back()->with('status', $status)->with('import_report', $report);
    }

    /**
     * Import one CSV row, returning whether the lecturer was created or updated
     * and whether its password was already hashed. Data problems raise an
     * exception the caller records without aborting the rest of the file.
     *
     * @param  array<string|int, string>  $row
     * @return array{status: string, hashed: bool}
     *
     * @throws RuntimeException
     */
    protected function importLecturerRow(array $row): array
    {
        // Documented structure: staff_id, lecturer_name, lecturer_email,
        // department_code, password. Headers are matched by name, so the
        // older lecturer_id / lecturer_name / lecturer_email order still
        // imports correctly.
        $staffId = $this->csvValue($row, 'staff_id', 0) ?? $this->csvValue($row, 'lecturer_id', 0);
        $name = $this->csvValue($row, 'lecturer_name', 1) ?? $this->csvValue($row, 'name', 1);
        $email = $this->csvValue($row, 'lecturer_email', 2) ?? $this->csvValue($row, 'email', 2);
        $departmentCode = $this->csvValue($row, 'department_code', 3) ?? $this->csvValue($row, 'department', 3);
        $password = $this->csvValue($row, 'password', 4);

        // The staff ID is the lecturer's key; a row without one cannot be
        // matched safely.
        if (blank($staffId)) {
            throw new RuntimeException('staff_id is required');
        }

        $emailProvided = filled($email);
        $email ??= mb_strtolower($staffId).'@lecturer.fsktm.test';
        $department = $this->resolveDepartment($departmentCode);

        if ($departmentCode && ! $department) {
            throw new RuntimeException("department_code \"{$departmentCode}\" was not found");
        }

        $attributes = [
            'name' => $name ?? $email,
            'staff_id' => $staffId,
            'department_id' => $department?->id,
            'role' => User::ROLE_ADVISOR,
        ];

        // Every row commits on its own, so a failure leaves no partial lecturer.
        return DB::transaction(function () use ($attributes, $staffId, $email, $emailProvided, $password) {
            // The staff ID is the lecturer's key: a row that keeps its staff ID
            // updates that lecturer even when the email column changes. Email
            // is accepted as a fallback for older sheets.
            $existing = User::whereRaw('lower(staff_id) = ?', [mb_strtolower($staffId)])->first()
                ?? User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();

            if ($existing && $existing->role !== User::ROLE_ADVISOR) {
                throw new RuntimeException("email \"{$email}\" belongs to an existing {$existing->role} account");
            }

            $hashed = Hash::isHashed((string) $password);

            if ($existing) {
                // Keep the login address in sync when the sheet supplies one,
                // unless another account already owns it.
                if ($emailProvided && ! User::where('email', $email)->where('id', '!=', $existing->id)->exists()) {
                    $attributes['email'] = $email;
                }

                $existing->updateWithStaffId($attributes);

                // A blank password leaves the current one untouched.
                if (filled($password)) {
                    $existing->update(['password' => $this->passwordFrom($password)]);
                }

                return ['status' => 'updated', 'hashed' => $hashed];
            }

            User::create($attributes + [
                'email' => $email,
                'password' => $this->passwordFrom($password),
            ]);

            return ['status' => 'created', 'hashed' => $hashed];
        });
    }

    /**
     * Turn a password cell into a value the auth guard can verify. A cell may
     * carry a plain password or an existing hash (e.g. exported from another
     * system) — hashing a hash would lock the account out for good, so a
     * usable hash is stored untouched. Blank cells fall back to the default.
     */
    protected function passwordFrom(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return Hash::make(self::DEFAULT_PASSWORD);
        }

        return Hash::isHashed($value) ? $value : Hash::make($value);
    }

    /**
     * Resolve a department from its code (e.g. "JKP"), falling back to the
     * department name.
     */
    protected function resolveDepartment(?string $value): ?Department
    {
        if (blank($value)) {
            return null;
        }

        $needle = mb_strtolower(trim($value));

        return Department::query()
            ->whereRaw('lower(code) = ?', [$needle])
            ->orWhereRaw('lower(name) = ?', [$needle])
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $lecturer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', 'unique:users,email'.($lecturer ? ','.$lecturer->id : '')],
            'staff_id' => ['required', 'string', 'max:30', 'unique:users,staff_id'.($lecturer ? ','.$lecturer->id : '')],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        if (filled($data['password'] ?? null)) {
            $data['password'] = $this->passwordFrom($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
