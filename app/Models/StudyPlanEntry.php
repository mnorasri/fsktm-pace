<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanEntry extends Model
{
    protected $fillable = [
        'user_id', 'course_plan_id', 'course_id', 'elective_slot_id', 'placeholder_key',
        'year', 'semester', 'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The "Elective Option" slot this row fills (Elektif I … VI) once the
     * student has chosen a course for it.
     */
    public function electiveSlot(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'elective_slot_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class, 'course_plan_id');
    }

    public function isPlaceholder(): bool
    {
        return filled($this->placeholder_key);
    }

    /**
     * True for the curriculum's "Elective Option" slots — while they are still
     * empty (course_id points at the slot itself) and once a course has been
     * chosen for them.
     */
    public function isElectiveOption(): bool
    {
        if (filled($this->elective_slot_id)) {
            return true;
        }

        return $this->courseTypeCode() === CourseType::ELECTIVE_OPTION;
    }

    /**
     * True when the row holds a programme "Elective Course" (CT07) — the courses
     * a student picks to fill one of the curriculum's Elective Option slots.
     */
    public function isElectiveCourse(): bool
    {
        return $this->courseTypeCode() === CourseType::ELECTIVE_COURSE;
    }

    /** The Elective Option course this row represents, e.g. "Elektif III". */
    public function electiveSlotCourse(): ?Course
    {
        return $this->electiveSlot
            ?? ($this->isElectiveOption() ? $this->course : null);
    }

    /** True once the student has picked a course for this elective slot. */
    public function hasElectiveChoice(): bool
    {
        return filled($this->elective_slot_id);
    }

    private function courseTypeCode(): ?string
    {
        $code = $this->course?->courseType?->code;

        return $code === null ? null : mb_strtoupper($code);
    }

    public function semesterKey(): string
    {
        return "Y{$this->year}S{$this->semester}";
    }

    public function orderKey(): int
    {
        return ($this->year * 10) + $this->semester;
    }
}
