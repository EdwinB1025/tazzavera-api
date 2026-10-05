<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable('name', 'description')]
class Roastery extends Model
{
    use HasPublicUlid, HasFactory;

    /**Modle Scopes */

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    #[Scope]
    protected function filter(Builder $query, array $validated): void
    {
        $scopes = ['name'];

        foreach ($scopes as $scope) {
            $query->{$scope}($validated);
        }
    }

    #[Scope]
    protected function name(Builder $query, array $validated): void
    {
        $query->when(
            $validated['name'] ?? null,
            fn($q, $v) => $q->where('name', 'like', "%{$v}%")
        );
    }

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

    public function coffeeInventory(): HasMany
    {
        return $this->hasMany(CoffeeInventory::class);
    }
}
