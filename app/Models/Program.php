<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = ['department_id', 'head_of_program_id', 'code', 'name', 'level', 'duration_years', 'total_credits'];

    protected $casts = [
        'duration_years' => 'float',
        'total_credits' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function headOfProgram(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_program_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(CoursePlan::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'program_id');
    }
}
