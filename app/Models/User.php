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
use Illuminate\Validation\ValidationException;

#[Fillable([
    'name', 'email', 'password', 'role', 'student_id', 'staff_id',
    'department_id', 'program_id', 'plan_id', 'advisor_id', 'phone',
    'is_hod', 'status', 'plan_status', 'plan_submitted_at',
    'plan_reviewed_by', 'plan_review_note', 'must_change_password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;

    public $usesUniqueIds = true;

    protected $keyType = 'string';

    /**
     * InnoDB cannot cascade a primary-key update back into the users table
     * itself. Detach and restore the self-references within the same transaction;
     * foreign keys carry the change through all other tables automatically.
     */
    public function save(array $options = []): bool
    {
        if (! $this->exists || ! $this->isDirty(['role', 'staff_id', 'student_id', 'id'])) {
            return parent::save($options);
        }

        $this->setUniqueIds();
        $oldId = (string) $this->getRawOriginal('id');

        if ($oldId === $this->id) {
            return parent::save($options);
        }

        $connection = $this->getConnection();

        return $connection->transaction(function () use ($connection, $oldId, $options) {
            $links = [];
            foreach (['advisor_id', 'plan_reviewed_by'] as $column) {
                $links[$column] = $connection->table('users')->where($column, $oldId)->pluck('id');
                $connection->table('users')->where($column, $oldId)->update([$column => null]);
            }

            $saved = parent::save($options);
            $newId = $saved ? $this->id : $oldId;

            foreach ($links as $column => $ids) {
                $ids = $ids->map(fn ($id) => (string) $id === $oldId ? $newId : $id);
                $connection->table('users')->whereIn('id', $ids)->whereNull($column)->update([$column => $newId]);
            }

            return $saved;
        });
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (! $user->exists || $user->isDirty(['role', 'staff_id', 'student_id'])) {
                $user->setUniqueIds();
            }
        });

        static::updated(function (User $user) {
            if ($user->wasChanged('id')) {
                $user->getConnection()->table('sessions')->whereIn('user_id', [
                    $user->getOriginal('id'), $user->id,
                ])->delete();
            }
        });
    }

    /**
     * Assign business identifiers before insert, including event-free seeding.
     * Administrators retain their numeric identifier in the shared string key.
     */
    public function setUniqueIds(): void
    {
        $this->role ??= self::ROLE_STUDENT;
        $field = $this->isStudent() ? 'student_id' : 'staff_id';

        if ($this->isAdmin()) {
            if (filled($this->id)) {
                return;
            }

            $maximum = $this->newQuery()->pluck('id')
                ->filter(fn ($id) => ctype_digit((string) $id))
                ->map(fn ($id) => (int) $id)->max() ?? 0;
            $id = (string) ($maximum + 1);
        } else {
            $id = (string) $this->getAttribute($field);
        }

        if (blank($id) || str_starts_with($id, '__user_key_')) {
            throw ValidationException::withMessages([$field => 'A valid '.$field.' is required.']);
        }

        if ($this->newQuery()->whereKey($id)
            ->when($this->exists, fn ($query) => $query->where('id', '!=', $this->getRawOriginal('id')))
            ->exists()) {
            throw ValidationException::withMessages([$field => 'This identifier is already used by another account.']);
        }

        if ($this->exists && $id !== (string) $this->getRawOriginal('id')) {
            $this->remember_token = null;
        }

        $this->id = $id;
    }

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
            'must_change_password' => 'boolean',
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

    /**
     * Programme IDs this user may manage in the curriculum area.
     *
     * Administrators and Heads of Department see everything (null = no
     * filter). A Head of Program is restricted to the programmes they
     * actually head. Anyone else gets an empty list (nothing visible).
     *
     * @return array<int>|null
     */
    public function curriculumProgramIds(): ?array
    {
        if ($this->isAdmin() || $this->isHod()) {
            return null;
        }

        if ($this->isHeadOfProgram()) {
            return $this->headedPrograms()->pluck('id')->all();
        }

        return [];
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
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function advisees(): HasMany
    {
        return $this->hasMany(User::class, 'advisor_id');
    }

    /**
     * Update this lecturer, carrying their advisees along when the staff ID
     * itself changes (students reference it as their advisor_id).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateWithStaffId(array $attributes): void
    {
        $this->getConnection()->transaction(fn () => $this->update($attributes));
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
