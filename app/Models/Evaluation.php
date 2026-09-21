<?php

namespace App\Models;

use App\Traits\HasPublicUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasPublicUlid, HasFactory;

    protected $casts = [
        'descriptive'   => 'array',
        'affective'     => 'array',
        'extrinsics'    => 'array',
        'is_defective'  => 'boolean',
        'cupping_score' => 'decimal:2',
    ];

    #[Scope]
    protected function filter(Builder $query, array $validated): void
    {
        $scopes = ['evaluatorId', 'coffeeId', 'city', 'locationId', 'process', 'score', 'status'];

        foreach ($scopes as $scope) {
            $query->{$scope}($validated);
        }

        $query->orderBy($validated);
    }

    #[Scope]
    protected function evaluatorId(Builder $query, array $validated): void
    {
        $query->when(
            $validated['evaluatorId'] ?? null,
            fn($q, $v) => $q->whereHas(
                'evaluator',
                fn($q) => $q->where('ulid', $v)
            )
        );
    }

    #[Scope]
    protected function coffeeId(Builder $query, array $validated): void
    {
        $query->when(
            $validated['coffeeId'] ?? null,
            fn($q, $v) => $q->whereHas(
                'offering.coffeeInventory.coffee',
                fn($q) => $q->where('ulid', $v)
            )
        );
    }

    #[Scope]
    protected function city(Builder $query, array $validated): void
    {
        $query->when(
            $validated['city'] ?? null,
            fn($q, $v) => $q->whereHas(
                'offering.location.contacts',
                fn($q) => $q->where('city', $v)
            )
        );
    }

    #[Scope]
    protected function locationId(Builder $query, array $validated): void
    {
        $query->when(
            $validated['locationId'] ?? null,
            fn($q, $v) => $q->whereHas(
                'offering.location',
                fn($q) => $q->where('ulid', $v)
            )
        );
    }

    #[Scope]
    protected function process(Builder $query, array $validated): void
    {
        $query->when(
            $validated['process'] ?? null,
            fn($q, $v) => $q->whereHas(
                'offering.coffeeInventory.coffee',
                fn($q) => $q->where('process', $v)
            )
        );
    }

    #[Scope]
    protected function score(Builder $query, array $validated): void
    {
        $query->when(
            $validated['scoreMin'] ?? null,
            fn($q, $v) => $q->where('cupping_score', '>=', $v)
        );

        $query->when(
            $validated['scoreMax'] ?? null,
            fn($q, $v) => $q->where('cupping_score', '<=', $v)
        );
    }

    #[Scope]
    protected function status(Builder $query, array $validated): void
    {
        $query->when(
            $validated['status'] ?? null,
            fn($q, $v) => $q->where('status', $v)
        );
    }

    #[Scope]
    protected function orderBy(Builder $query, array $validated): void
    {
        $query->when(
            $validated['orderBy'] ?? null,
            fn($q, $v) => $q->orderBy($v, $validated['orderDirection'] ?? 'asc')
        );
    }


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
