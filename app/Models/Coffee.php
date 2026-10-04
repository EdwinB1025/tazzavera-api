<?php

namespace App\Models;

use App\Enums\RoastLevel;
use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(
    'name',
    'roast_level',
    'process',
    'variety',
    'country',
    'region',
    'altitude',
    'lot',
)]
class Coffee extends Model
{
    use HasPublicUlid, HasFactory;

    protected $casts = [
        'roast_level' => RoastLevel::class,
    ];

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

    /**Relationships */

    public function certificationTypes(): BelongsToMany
    {
        return $this->belongsToMany(CertificationType::class, 'certifications')
            ->withPivot('issued_at', 'expires_at');
    }

    public function rosteries(): BelongsToMany
    {
        return $this->belongsToMany(Roastery::class)
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
