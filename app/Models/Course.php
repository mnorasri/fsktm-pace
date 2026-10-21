<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'code', 'name', 'credits', 'course_load', 'semester',
        'department_id', 'program_id', 'course_plan_id',
        'course_type_id', 'mqf_sc_id', 'vbe_id', 'sdg_id', 'alternative_group',
        'prerequisites', 'synopsis', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'course_load' => 'float',
            'semester' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function coursePlan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'course_plan_id');
    }

    public function sdg(): BelongsTo
    {
        return $this->belongsTo(Sdg::class, 'sdg_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    public function mqfSc(): BelongsTo
    {
        return $this->belongsTo(MqfSc::class, 'mqf_sc_id');
    }

    public function vbe(): BelongsTo
    {
        return $this->belongsTo(Vbe::class, 'vbe_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class);
    }

    /**
     * Prerequisite course codes as an array.
     *
     * @return array<int, string>
     */
    public function prerequisiteCodes(): array
    {
        if (blank($this->prerequisites)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->prerequisites))));
    }

    public function prerequisiteCourses()
    {
        $codes = $this->prerequisiteCodes();

        if (empty($codes)) {
            return collect();
        }

        return static::whereIn('code', $codes)->get();
    }
}
