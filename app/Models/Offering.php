<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('location_id', 'coffee_inventory_id')]
class Offering extends Model
{
    use HasPublicUlid;

    protected $casts = [
        'evaluation_count' => 'integer',
        'defective_evaluation_count' => 'integer',
        'cupping_avg' => 'decimal:2',
        'fragrance_avg' => 'decimal:1',
        'aroma_avg' => 'decimal:1',
        'flavor_avg' => 'decimal:1',
        'aftertaste_avg' => 'decimal:1',
        'acidity_avg' => 'decimal:1',
        'sweetness_avg' => 'decimal:1',
        'mouthfeel_avg' => 'decimal:1',
        'overall_avg' => 'decimal:1',
        'concordance' => 'decimal:3',
        'verification_status' => 'string',
    ];

    /** Relationships */

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function coffeeInventory(): BelongsTo
    {
        return $this->belongsTo(CoffeeInventory::class);
    }

    public function offeringTastes(): HasMany
    {
        return $this->hasMany(OfferingTaste::class);
    }
}
