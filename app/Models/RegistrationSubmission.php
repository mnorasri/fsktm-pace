<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's pre-registration for one term, awaiting (or holding) the
 * advisor's decision. No row means the student has not submitted yet.
 */
class RegistrationSubmission extends Model
{
    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id', 'session', 'semester', 'status',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * A submitted or approved registration is locked: the student may not add
     * or withdraw courses until the advisor returns it.
     */
    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_APPROVED], true);
    }

    public static function forTerm(User $student, string $session, string $semester): ?self
    {
        return static::query()
            ->where('user_id', $student->id)
            ->where('session', $session)
            ->where('semester', $semester)
            ->first();
    }
}
