<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(
    'extraction_method',
    'descriptive',
    'affective',
    'extrinsics',
)]
class Evaluation extends Model
{
    use HasPublicUlid;

    protected $casts = [
        'descriptive'   => 'array',
        'affective'     => 'array',
        'extrinsics'    => 'array',
        'is_defective'  => 'boolean',
        'cupping_score' => 'decimal:2',
    ];

    /** Relationship */

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function tastes(): HasMany
    {
        return $this->hasMany(EvaluationTaste::class);
    }
}
