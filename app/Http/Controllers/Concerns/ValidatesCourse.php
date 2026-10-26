<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Course;
use Illuminate\Http\Request;

/**
 * Course form rules shared by the administrator's course directory and the
 * Head of Department's own copy of it, so both accept exactly the same data.
 * The reference columns are entered and stored as their codes.
 */
trait ValidatesCourse
{
    /**
     * @return array<string, mixed>
     */
    protected function validatedCourse(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'course_code' => ['required', 'string', 'max:30', 'unique:courses,course_code'.($course ? ','.$course->id : '')],
            'course_name_en' => ['required', 'string', 'max:180'],
            'course_name_my' => ['nullable', 'string', 'max:180'],
            'course_type_code' => ['nullable', 'string', 'exists:course_types,code'],
            'credit_hours' => ['required', 'integer', 'min:1', 'max:20'],
            'course_load' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:10'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'course_plan_id' => ['nullable', 'exists:course_plans,id'],
            'pre_requisite' => ['nullable', 'string', 'max:255'],
            'sc_code' => ['nullable', 'string', 'exists:mqf_sc,code'],
            'vbe_ept_code' => ['nullable', 'string', 'exists:vbe,code'],
            'sdg_code' => ['nullable', 'string', 'exists:sdgs,code'],
            'alternative_group' => ['nullable', 'string', 'max:60'],
        ]);
    }
}
