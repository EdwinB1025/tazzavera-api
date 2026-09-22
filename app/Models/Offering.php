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

#[Fillable('location_id', 'coffee_inventory_id')]
class Offering extends Model
{
    use HasPublicUlid, HasFactory;

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
        'concordance_affective' => 'decimal:3',
        'concordance_descriptive' => 'decimal:3',
    ];

    #[Scope]
    protected function filter(Builder $query, array $validated): void
    {
        $scopes = [
            'coffeeName',
            'originCountry',
            'originRegion',
            'process',
            'producer',
            'variety',
            'city',
            'coffeeshopUlid',
            'locationUlid',
            'cuppingAvg',
            'evaluationCount',
            'defectiveCount',
            'fragrance',
            'aroma',
            'flavor',
            'aftertaste',
            'acidity',
            'sweetness',
            'mouthfeel',
            'overall',
            'cataRef',
            'fragranceCata',
            'aromaCata',
            'flavorCata',
            'aftertasteCata',
            'mouthfeelCata',
        ];

        foreach ($scopes as $scope) {
            $query->{$scope}($validated);
        }

        $query->applyOrderBy($validated);
    }

    #[Scope]
    protected function coffeeName(Builder $query, array $validated): void
    {
        $query->when(
            $validated['coffeeName'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('name', 'like', "%{$v}%"))
        );
    }

    #[Scope]
    protected function originCountry(Builder $query, array $validated): void
    {
        $query->when(
            $validated['originCountry'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('country', $v))
        );
    }

    #[Scope]
    protected function originRegion(Builder $query, array $validated): void
    {
        $query->when(
            $validated['originRegion'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('region', $v))
        );
    }

    #[Scope]
    protected function process(Builder $query, array $validated): void
    {
        $query->when(
            $validated['process'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('process', $v))
        );
    }

    #[Scope]
    protected function producer(Builder $query, array $validated): void
    {
        $query->when(
            $validated['producer'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('producer', $v))
        );
    }

    #[Scope]
    protected function variety(Builder $query, array $validated): void
    {
        $query->when(
            $validated['variety'] ?? null,
            fn($q, $v) => $q->whereHas('coffeeInventory.coffee', fn($q) => $q->where('variety', $v))
        );
    }

    #[Scope]
    protected function city(Builder $query, array $validated): void
    {
        $query->when(
            $validated['city'] ?? null,
            fn($q, $v) => $q->whereHas('location.contacts', fn($q) => $q->where('city', $v))
        );
    }

    #[Scope]
    protected function coffeeshopUlid(Builder $query, array $validated): void
    {
        $query->when(
            $validated['coffeeshopUlid'] ?? null,
            fn($q, $v) => $q->whereHas('location.user', fn($q) => $q->where('ulid', $v))
        );
    }

    #[Scope]
    protected function locationUlid(Builder $query, array $validated): void
    {
        $query->when(
            $validated['locationUlid'] ?? null,
            fn($q, $v) => $q->whereHas('location', fn($q) => $q->where('ulid', $v))
        );
    }

    #[Scope]
    protected function cuppingAvg(Builder $query, array $validated): void
    {
        $query->when($validated['cuppingAvgMin'] ?? null, fn($q, $v) => $q->where('cupping_avg', '>=', $v));
        $query->when($validated['cuppingAvgMax'] ?? null, fn($q, $v) => $q->where('cupping_avg', '<=', $v));
    }

    #[Scope]
    protected function evaluationCount(Builder $query, array $validated): void
    {
        $query->when($validated['evaluationCountMin'] ?? null, fn($q, $v) => $q->where('evaluation_count', '>=', $v));
        $query->when($validated['evaluationCountMax'] ?? null, fn($q, $v) => $q->where('evaluation_count', '<=', $v));
    }

    #[Scope]
    protected function defectiveCount(Builder $query, array $validated): void
    {
        $query->when($validated['defectiveCountMin'] ?? null, fn($q, $v) => $q->where('defective_evaluation_count', '>=', $v));
        $query->when($validated['defectiveCountMax'] ?? null, fn($q, $v) => $q->where('defective_evaluation_count', '<=', $v));
    }

    #[Scope]
    protected function fragrance(Builder $query, array $validated): void
    {
        $query->when($validated['fragranceMin'] ?? null, fn($q, $v) => $q->where('fragrance_avg', '>=', $v));
        $query->when($validated['fragranceMax'] ?? null, fn($q, $v) => $q->where('fragrance_avg', '<=', $v));
    }

    #[Scope]
    protected function aroma(Builder $query, array $validated): void
    {
        $query->when($validated['aromaMin'] ?? null, fn($q, $v) => $q->where('aroma_avg', '>=', $v));
        $query->when($validated['aromaMax'] ?? null, fn($q, $v) => $q->where('aroma_avg', '<=', $v));
    }

    #[Scope]
    protected function flavor(Builder $query, array $validated): void
    {
        $query->when($validated['flavorMin'] ?? null, fn($q, $v) => $q->where('flavor_avg', '>=', $v));
        $query->when($validated['flavorMax'] ?? null, fn($q, $v) => $q->where('flavor_avg', '<=', $v));
    }

    #[Scope]
    protected function aftertaste(Builder $query, array $validated): void
    {
        $query->when($validated['aftertasteMin'] ?? null, fn($q, $v) => $q->where('aftertaste_avg', '>=', $v));
        $query->when($validated['aftertasteMax'] ?? null, fn($q, $v) => $q->where('aftertaste_avg', '<=', $v));
    }

    #[Scope]
    protected function acidity(Builder $query, array $validated): void
    {
        $query->when($validated['acidityMin'] ?? null, fn($q, $v) => $q->where('acidity_avg', '>=', $v));
        $query->when($validated['acidityMax'] ?? null, fn($q, $v) => $q->where('acidity_avg', '<=', $v));
    }

    #[Scope]
    protected function sweetness(Builder $query, array $validated): void
    {
        $query->when($validated['sweetnessMin'] ?? null, fn($q, $v) => $q->where('sweetness_avg', '>=', $v));
        $query->when($validated['sweetnessMax'] ?? null, fn($q, $v) => $q->where('sweetness_avg', '<=', $v));
    }

    #[Scope]
    protected function mouthfeel(Builder $query, array $validated): void
    {
        $query->when($validated['mouthfeelMin'] ?? null, fn($q, $v) => $q->where('mouthfeel_avg', '>=', $v));
        $query->when($validated['mouthfeelMax'] ?? null, fn($q, $v) => $q->where('mouthfeel_avg', '<=', $v));
    }

    #[Scope]
    protected function overall(Builder $query, array $validated): void
    {
        $query->when($validated['overallMin'] ?? null, fn($q, $v) => $q->where('overall_avg', '>=', $v));
        $query->when($validated['overallMax'] ?? null, fn($q, $v) => $q->where('overall_avg', '<=', $v));
    }

    #[Scope]
    protected function cataRef(Builder $query, array $validated): void
    {
        $query->when(
            $validated['cataRef'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes.taxonomy', fn($q) => $q->whereIn('ulid', $v))
        );
    }

    #[Scope]
    protected function fragranceCata(Builder $query, array $validated): void
    {
        $query->when(
            $validated['fragranceCata'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes', fn($q) => $q->where('type', 'fragrance')->whereHas('taxonomy', fn($q) => $q->whereIn('ulid', $v)))
        );
    }

    #[Scope]
    protected function aromaCata(Builder $query, array $validated): void
    {
        $query->when(
            $validated['aromaCata'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes', fn($q) => $q->where('type', 'aroma')->whereHas('taxonomy', fn($q) => $q->whereIn('ulid', $v)))
        );
    }

    #[Scope]
    protected function flavorCata(Builder $query, array $validated): void
    {
        $query->when(
            $validated['flavorCata'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes', fn($q) => $q->where('type', 'flavor')->whereHas('taxonomy', fn($q) => $q->whereIn('ulid', $v)))
        );
    }

    #[Scope]
    protected function aftertasteCata(Builder $query, array $validated): void
    {
        $query->when(
            $validated['aftertasteCata'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes', fn($q) => $q->where('type', 'aftertaste')->whereHas('taxonomy', fn($q) => $q->whereIn('ulid', $v)))
        );
    }

    #[Scope]
    protected function mouthfeelCata(Builder $query, array $validated): void
    {
        $query->when(
            $validated['mouthfeelCata'] ?? null,
            fn($q, $v) => $q->whereHas('offeringTastes', fn($q) => $q->where('type', 'mouthfeel')->whereHas('taxonomy', fn($q) => $q->whereIn('ulid', $v)))
        );
    }

    #[Scope]
    protected function applyOrderBy(Builder $query, array $validated): void
    {
        $query->when(
            $validated['orderBy'] ?? null,
            fn($q, $v) => $q->orderBy($v, $validated['orderDirection'] ?? 'asc')
        );
    }

    /** Relationships */

    public function axisConcordances(): HasMany
    {
        return $this->hasMany(AxisConcordance::class);
    }

    public function coffeeInventory(): BelongsTo
    {
        return $this->belongsTo(CoffeeInventory::class);
    }

    public function offeringTastes(): HasMany
    {
        return $this->hasMany(OfferingTaste::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
