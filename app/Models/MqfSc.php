<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MqfSc extends Model
{
    protected $table = 'mqf_sc';

    protected $fillable = ['code', 'name', 'description'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'sc_code', 'code');
    }
}
