<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sdg extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'sdg_code', 'code');
    }

    /**
     * Order SDG1 … SDG17 numerically rather than lexicographically.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('LENGTH(code), code');
    }
}
