<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        // Curriculum structure
        'course_code', 'course_name_en', 'course_name_my', 'credit_hours',
        'course_load', 'course_type_code', 'pre_requisite',
        'sc_code', 'vbe_ept_code', 'sdg_code',
        // Supplementary fields the study plan, offering and elective rules need
        'semester', 'department_id', 'program_id', 'course_plan_id',
        'alternative_group', 'synopsis', 'is_active',
        // Backwards-compatible aliases (mapped by the accessors below)
        'code', 'name', 'credits', 'prerequisites',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'course_load' => 'float',
            'semester' => 'integer',
            'credit_hours' => 'integer',
        ];
    }

    /* -----------------------------------------------------------------
     * Curriculum structure aliases
     *
     * Older pages read $course->code / ->name / ->credits / ->prerequisites.
     * Keep those reads and writes working while the database uses the new
     * column names everywhere.
     * ----------------------------------------------------------------- */

    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->course_code,
            set: fn (?string $value) => ['course_code' => $value],
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->course_name_en,
            set: fn (?string $value) => ['course_name_en' => $value],
        );
    }

    protected function credits(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->credit_hours,
            set: fn ($value) => ['credit_hours' => $value],
        );
    }

    protected function prerequisites(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pre_requisite,
            set: fn (?string $value) => ['pre_requisite' => $value],
        );
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
        return $this->belongsTo(Sdg::class, 'sdg_code', 'code');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class, 'course_type_code', 'code');
    }

    public function mqfSc(): BelongsTo
    {
        return $this->belongsTo(MqfSc::class, 'sc_code', 'code');
    }

    public function vbe(): BelongsTo
    {
        return $this->belongsTo(Vbe::class, 'vbe_ept_code', 'code');
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
        if (blank($this->pre_requisite)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $this->pre_requisite))));
    }

    /**
     * The prerequisite courses themselves, looked up by their course codes.
     *
     * @return Collection<int, Course>
     */
    public function prerequisiteCourses()
    {
        $codes = $this->prerequisiteCodes();

        if (empty($codes)) {
            return collect();
        }

        return static::whereIn('course_code', $codes)->get();
    }
}
