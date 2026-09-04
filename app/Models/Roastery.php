<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable('name', 'description')]
class Roastery extends Model
{
    use HasPublicUlid, HasFactory;

    /** Relationships */

    public function contacts()
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function coffees(): BelongsToMany
    {
        return $this->belongsToMany(Coffee::class)
            ->using(CoffeeInventory::class)
            ->withPivot(['roast_lot', 'production_date'])
            ->as('inventory')
            ->withTimestamps();
    }
}
