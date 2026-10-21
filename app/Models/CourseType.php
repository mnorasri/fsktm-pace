<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseType extends Model
{
    /** "Elective Course" — a course the student may pick for an elective slot. */
    public const ELECTIVE_COURSE = 'CT07';

    /** "Elective Option" — the study plan slot an elective course fills. */
    public const ELECTIVE_OPTION = 'CT07A';

    protected $fillable = ['code', 'name', 'description'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
