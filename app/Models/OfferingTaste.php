<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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

    public const AROMATIC_TYPES = ['fragrance', 'aroma', 'flavor', 'aftertaste', 'mouthfeel'];
    public const AROMATIC_GROUP = 'aromatics';

    /** Query scopes */
    #[Scope]
    protected function sensoryAxes(Builder $query): void
    {
        $query->where('type', '!=', self::AROMATIC_GROUP);
    }

    #[Scope]
    protected function cataAggregate(Builder $query): void
    {
        $query->whereNotIn('type', self::AROMATIC_TYPES);
    }

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

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
