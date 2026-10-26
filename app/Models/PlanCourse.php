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

    /**
     * The curriculum semester this row sits in, as the single number Study Plan
     * Details uses. Odd semesters are Semester 1 of the year, even ones are
     * Semester 2; the short special semester is reported as its year's odd
     * semester (the view flags it separately).
     */
    public function curriculumSemester(): ?int
    {
        if ($this->year === null || $this->semester === null) {
            return null;
        }

        return ((int) $this->year - 1) * 2 + ((int) $this->semester === 2 ? 2 : 1);
    }

    /**
     * True when the row was placed in the short special semester.
     */
    public function isSpecialSemester(): bool
    {
        return (int) $this->semester === 3;
    }
}
