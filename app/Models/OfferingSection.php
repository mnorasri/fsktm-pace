<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferingSection extends Model
{
    protected $fillable = ['offering_id', 'section', 'lecturer_id'];

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
