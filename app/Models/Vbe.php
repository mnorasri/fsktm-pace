<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vbe extends Model
{
    protected $table = 'vbe';

    protected $fillable = ['code', 'name', 'description'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'vbe_ept_code', 'code');
    }
}
