<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePlan extends Model
{
    protected $fillable = ['program_id', 'code', 'name', 'version', 'total_credits', 'description'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function planCourses(): HasMany
    {
        return $this->hasMany(PlanCourse::class);
    }

    /**
     * Courses attached to this plan (the offering grid reads these).
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'course_plan_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'plan_id');
    }
}
