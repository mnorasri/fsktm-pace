<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'name', 'email', 'password', 'role', 'student_id', 'staff_id',
    'department_id', 'program_id', 'plan_id', 'advisor_id', 'phone',
    'is_hod', 'status', 'plan_status', 'plan_submitted_at',
    'plan_reviewed_by', 'plan_review_note',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ADVISOR = 'advisor';

    public const ROLE_STUDENT = 'student';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_hod' => 'boolean',
            'plan_submitted_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Role resolution (Firestore mirror: students/advisors/hods/admin)
    // ---------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAdvisor(): bool
    {
        return $this->role === self::ROLE_ADVISOR;
    }

    public function isHod(): bool
    {
        return $this->role === self::ROLE_ADVISOR && $this->is_hod;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * A Head of Program is an advisor appointed to lead at least one programme.
     */
    public function isHeadOfProgram(): bool
    {
        return $this->isAdvisor() && $this->headedPrograms()->exists();
    }

    public function roleLabel(): string
    {
        return match (true) {
            $this->isAdmin() => 'System Administrator',
            $this->isHod() => 'Head of Department',
            $this->isHeadOfProgram() => 'Head of Program',
            $this->isAdvisor() => 'Academic & Career Advisor',
            default => 'Student',
        };
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->isAdmin() => 'admin.dashboard',
            $this->isAdvisor() => 'advisor.dashboard',
            default => 'student.dashboard',
        };
    }

    // ---------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'plan_id');
    }

    /**
     * A student's advisor_id holds the advisor's staff ID (e.g. "norasri").
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id', 'staff_id');
    }

    public function advisees(): HasMany
    {
        return $this->hasMany(User::class, 'advisor_id', 'staff_id');
    }

    /**
     * Update this lecturer, carrying their advisees along when the staff ID
     * itself changes (students reference it as their advisor_id).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateWithStaffId(array $attributes): void
    {
        $newStaffId = $attributes['staff_id'] ?? $this->staff_id;

        if (blank($this->staff_id) || blank($newStaffId) || $newStaffId === $this->staff_id) {
            $this->update($attributes);

            return;
        }

        DB::transaction(function () use ($attributes, $newStaffId) {
            $adviseeIds = static::query()->where('advisor_id', $this->staff_id)->pluck('id');

            // The constraint blocks a rename while students still point at the
            // old value, so detach them for the moment the parent changes.
            static::query()->whereIn('id', $adviseeIds)->update(['advisor_id' => null]);
            $this->update($attributes);
            static::query()->whereIn('id', $adviseeIds)->update(['advisor_id' => $newStaffId]);
        });
    }

    public function registrationSubmissions(): HasMany
    {
        return $this->hasMany(RegistrationSubmission::class, 'user_id');
    }

    public function headedPrograms(): HasMany
    {
        return $this->hasMany(Program::class, 'head_of_program_id');
    }

    public function studyPlanEntries(): HasMany
    {
        return $this->hasMany(StudyPlanEntry::class);
    }

    public function preRegistrations(): HasMany
    {
        return $this->hasMany(PreRegistration::class);
    }

    // ---------------------------------------------------------------
    // Credit helpers used across the student / advisor portals
    // ---------------------------------------------------------------

    /**
     * Total credits defined by the student's curriculum plan.
     */
    public function requiredCredits(): int
    {
        if ($this->plan && $this->plan->total_credits) {
            return (int) $this->plan->total_credits;
        }

        if ($this->program && $this->program->total_credits) {
            return (int) $this->program->total_credits;
        }

        return 120;
    }

    public function earnedCredits(): int
    {
        return (int) $this->studyPlanEntries()
            ->whereIn('status', ['COMPLETED', 'EXEMPTED'])
            ->with('course')
            ->get()
            ->sum(fn (StudyPlanEntry $entry) => $entry->course->credits ?? 0);
    }

    public function plannedCredits(): int
    {
        return (int) $this->studyPlanEntries()
            ->where('status', 'PLANNED')
            ->with('course')
            ->get()
            ->sum(fn (StudyPlanEntry $entry) => $entry->course->credits ?? 0);
    }

    /**
     * @return array{required:int, earned:int, planned:int, remaining:int, percent:int}
     */
    public function creditSummary(): array
    {
        $required = $this->requiredCredits();
        $earned = $this->earnedCredits();
        $planned = $this->plannedCredits();
        $remaining = max(0, $required - $earned);

        return [
            'required' => $required,
            'earned' => $earned,
            'planned' => $planned,
            'remaining' => $remaining,
            'percent' => $required > 0 ? (int) round(($earned / $required) * 100) : 0,
        ];
    }
}
