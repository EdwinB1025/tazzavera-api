<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('offering_id', 'taxonomy_ref', 'type', 'level', 'parent_id', 'count')]
class OfferingTaste extends Model
{
    protected $casts = [
        'level' => 'integer',
        'count' => 'integer',
    ];

    /**Relationships */

    public function offering(): BelongsTo
    {
        return $this->belongsTo(Offering::class);
    }

    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(OlfactoryTaxonomy::class, 'taxonomy_ref');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OfferingTaste::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OfferingTaste::class, 'parent_id');
    }
}
