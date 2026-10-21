<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferingGroup extends Model
{
    protected $fillable = [
        'offering_id', 'advisor_id', 'section', 'lecturer_id',
    ];

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    /** The PAK group (academic advisor) these students belong to. */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }
}
