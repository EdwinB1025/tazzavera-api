<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable('offering_id', 'cva_type', 'axis', 'value')]
class AxisConcordance extends Model
{
    protected $casts = [
        'value' => 'decimal:3',
    ];

    /**Relationsips */
    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }
}
