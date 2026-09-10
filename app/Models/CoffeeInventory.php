<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(
    'roastery_id',
    'coffee_id',
    'roast_lot',
    'production_date',
)]
#[Table('coffee_inventory')]
class CoffeeInventory extends Pivot
{
    use HasPublicUlid, HasFactory;

    /**Modle Scopes */

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    #[Scope]
    protected function filter(Builder $query, array $validated): void
    {
        $scopes = ['originCountry', 'coffeeName', 'process', 'producer', 'originRegion', 'city'];

        foreach ($scopes as $scope) {
            $query->{$scope}($validated);
        }
    }

    #[Scope]
    protected function coffeeName(Builder $query, array $validated): void
    {
        $query->when(
            $validated['coffeeName'] ?? null,
            fn($q, $v) => $q->whereHas(
                'coffee',
                fn($q) => $q->where('name', 'like', "%{$v}%")
            )
        );
    }

    #[Scope]
    protected function originCountry(Builder $query, array $validated): void
    {
        $query->when(
            $validated['originCountry'] ?? null,
            fn($q, $v) => $q->whereHas(
                'coffee',
                fn($q) => $q->where('country', $v)
            )
        );
    }

    #[Scope]
    protected function originRegion(Builder $query, array $validated): void
    {
        $query->when(
            $validated['originRegion'] ?? null,
            fn($q, $v) => $q->whereHas(
                'coffee',
                fn($q) => $q->where('region', $v)
            )
        );
    }

    #[Scope]
    protected function process(Builder $query, array $validated): void
    {
        $query->when(
            $validated['process'] ?? null,
            fn($q, $v) => $q->whereHas(
                'coffee',
                fn($q) => $q->where('process', $v)
            )
        );
    }

    #[Scope]
    protected function producer(Builder $query, array $validated): void
    {
        $query->when(
            $validated['producer'] ?? null,
            fn($q, $v) => $q->whereHas(
                'coffee',
                fn($q) => $q->where('producer', $v)
            )
        );
    }

    #[Scope]
    protected function city(Builder $query, array $validated): void
    {
        $query->when(
            $validated['city'] ?? null,
            fn($q, $v) => $q->whereHas(
                'roastery.contacts',
                fn($q) => $q->where('city', $v)
            )
        );
    }

    /**Relationships */

    public function coffee(): BelongsTo
    {
        return $this->belongsTo(Coffee::class);
    }

    public function roastery(): BelongsTo
    {
        return $this->belongsTo(Roastery::class);
    }
}
