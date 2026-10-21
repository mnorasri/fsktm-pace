<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LecturerController extends Controller
{
    use ImportsCsv;

    /**
     * The password every account starts with when none is supplied.
     */
    public const DEFAULT_PASSWORD = 'password';

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

        $lecturer->update($this->validated($request, $lecturer));

        return back()->with('status', 'Lecturer account updated.');
    }

    public function destroy(User $lecturer): RedirectResponse
    {
        abort_unless($lecturer->role === User::ROLE_ADVISOR, 404);

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

        foreach ($this->readCsv($request->file('file')) as $row) {
            // Documented structure: lecturer_name, lecturer_email, lecturer_id,
            // department_code, password.
            $name = $this->csvValue($row, 'lecturer_name', 0) ?? $this->csvValue($row, 'name', 0);
            $email = $this->csvValue($row, 'lecturer_email', 1) ?? $this->csvValue($row, 'email', 1);
            $staffId = $this->csvValue($row, 'lecturer_id', 2) ?? $this->csvValue($row, 'staff_id', 2);
            $departmentCode = $this->csvValue($row, 'department_code', 3) ?? $this->csvValue($row, 'department', 3);
            $password = $this->csvValue($row, 'password', 4);

            if (blank($email) && blank($staffId)) {
                continue;
            }

            $email ??= mb_strtolower($staffId).'@lecturer.fsktm.test';
            $department = $this->resolveDepartment($departmentCode);

            $attributes = [
                'name' => $name ?? $email,
                'staff_id' => $staffId,
                'department_id' => $department?->id,
                'role' => User::ROLE_ADVISOR,
            ];

            $existing = User::whereRaw('lower(email) = ?', [mb_strtolower($email)])->first();

            if (! $existing && $staffId) {
                $existing = User::whereRaw('lower(staff_id) = ?', [mb_strtolower($staffId)])->first();
            }

            if (Hash::isHashed((string) $password)) {
                $hashed++;
            }

            if ($existing) {
                $existing->update($attributes);

                // A blank password leaves the current one untouched.
                if (filled($password)) {
                    $existing->update(['password' => $this->passwordFrom($password)]);
                }

                $updated++;
            } else {
                User::create($attributes + [
                    'email' => $email,
                    'password' => $this->passwordFrom($password),
                ]);
                $created++;
            }
        }

        $status = "Lecturer import complete: {$created} created, {$updated} updated. "
            .'Default password: '.self::DEFAULT_PASSWORD.'.';

        if ($hashed > 0) {
            $status .= " {$hashed} row(s) supplied an already-hashed password and were kept as-is.";
        }

        return back()->with('status', $status);
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
            'staff_id' => ['nullable', 'string', 'max:30', 'unique:users,staff_id'.($lecturer ? ','.$lecturer->id : '')],
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
