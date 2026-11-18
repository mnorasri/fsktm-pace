<?php

namespace App\Models;

use Database\Factories\CourseOfferingRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOfferingRequest extends Model
{
    /** @use HasFactory<CourseOfferingRequestFactory> */
    use HasFactory;

    public const PENDING_ADVISOR = 'pending_advisor';

    public const PENDING_HOD = 'pending_hod';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'course_id', 'session', 'semester', 'status', 'reason',
        'advisor_reviewed_by', 'advisor_note', 'advisor_reviewed_at', 'forwarded_at',
        'hod_reviewed_by', 'hod_note', 'hod_reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'advisor_reviewed_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'hod_reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function advisorReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_reviewed_by');
    }

    public function hodReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hod_reviewed_by');
    }

    public function statusLabel(bool $registered = true): string
    {
        return match ($this->status) {
            self::PENDING_ADVISOR => 'Awaiting advisor review',
            self::PENDING_HOD => 'Forwarded to Head of Department',
            self::APPROVED => $registered ? 'Approved — student registered' : 'Approved — registration pending',
            self::REJECTED => $this->hod_reviewed_at ? 'Rejected by Head of Department' : 'Rejected by advisor',
        };
    }
}
