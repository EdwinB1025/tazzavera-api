<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(
    'evaluation_id',
    'taxonomy_ref',
    'type',
)]
class EvaluationTaste extends Model
{

    /** Relationship */

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(OlfactoryTaxonomy::class, 'taxonomy_ref');
    }
}
