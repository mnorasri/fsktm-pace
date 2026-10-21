<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanCourse extends Model
{
    protected $fillable = [
        'course_plan_id', 'course_id', 'placeholder_key',
        'year', 'semester', 'category', 'credits',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'course_plan_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isPlaceholder(): bool
    {
        return filled($this->placeholder_key);
    }
}
