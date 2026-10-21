<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offering extends Model
{
    protected $fillable = [
        'course_id', 'session', 'semester', 'section', 'program_id',
        'course_plan_id', 'year', 'lecturer_id', 'coordinator_id',
        'advisee_group_id', 'capacity', 'is_offered', 'day', 'time', 'venue',
    ];

    protected function casts(): array
    {
        return [
            'is_offered' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'course_plan_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function adviseeGroup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisee_group_id');
    }

    public function preRegistrations(): HasMany
    {
        return $this->hasMany(PreRegistration::class);
    }

    /**
     * The per-PAK-group sections of this offering: each advisor group gets its
     * own section and lecturer.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(OfferingGroup::class)->orderBy('advisor_id');
    }
}
