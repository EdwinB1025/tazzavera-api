<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasPublicUlid;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'surname', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements PasskeyUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasPublicUlid, Notifiable, PasskeyAuthenticatable, SoftDeletes, Prunable, TwoFactorAuthenticatable;

    protected $guard_name = ['web', 'api'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** EDB 10/07/26: public coffee shop directory: coffeeshop users with at least one location */
    #[Scope]
    protected function coffeeshops(Builder $query): void
    {
        $query->role('coffeeshop')->has('locations');
    }

    /** EDB 10/07/26: public coffee shop directory counters */
    #[Scope]
    protected function withCoffeeshopCounts(Builder $query): void
    {
        $query->withCount($this->coffeeshopCounts());
    }

    /** Coffee shop directory counters on a loaded user, as the scope withCoffeeshopCounts */
    public function loadCoffeeshopCounts(): static
    {
        return $this->loadCount($this->coffeeshopCounts());
    }

    /** Whether the user is listed in the public coffee shop directory (scope coffeeshops) */
    public function isListedCoffeeshop(): bool
    {
        return static::query()->coffeeshops()->whereKey($this->getKey())->exists();
    }

    private function coffeeshopCounts(): array
    {
        return [
            'locations',
            'offerings',
            'offerings as verified_offerings_count' => fn($q) => $q->where('verification_status', 'verified'),
        ];
    }

    /** EDB 10/06/26: public coffee shop directory filters (FilterCoffeeshopRequest) */
    #[Scope]
    protected function filter(Builder $query, array $validated): void
    {
        $scopes = [
            'name',
            'city',
            'postalCode',
            'verified',
        ];

        foreach ($scopes as $scope) {
            $query->{$scope}($validated);
        }

        $query->applyOrderBy($validated);
    }

    #[Scope]
    protected function name(Builder $query, array $validated): void
    {
        $query->when(
            $validated['name'] ?? null,
            fn($q, $v) => $q->where('name', 'like', "%{$v}%")
        );
    }

    #[Scope]
    protected function city(Builder $query, array $validated): void
    {
        $query->when(
            $validated['city'] ?? null,
            fn($q, $v) => $q->whereHas('locations.primaryContact', fn($q) => $q->where('city', $v))
        );
    }

    #[Scope]
    protected function postalCode(Builder $query, array $validated): void
    {
        $query->when(
            $validated['postalCode'] ?? null,
            fn($q, $v) => $q->whereHas('locations.primaryContact', fn($q) => $q->where('postal_code', $v))
        );
    }

    #[Scope]
    protected function verified(Builder $query, array $validated): void
    {
        // verified offerings, not a verified email
        $query->when(
            isset($validated['verified']), // isset: verified=0 is a valid filter value, not an absent one
            fn($q) => $validated['verified']
                ? $q->whereHas('offerings', fn($q) => $q->where('verification_status', 'verified'))
                : $q->whereDoesntHave('offerings', fn($q) => $q->where('verification_status', 'verified'))
        );
    }

    #[Scope]
    protected function applyOrderBy(Builder $query, array $validated): void
    {
        $query->when(
            $validated['orderBy'] ?? null, // default name: FilterCoffeeshopRequest::prepareForValidation
            fn($q, $v) => $q->orderBy($v, $validated['orderDirection'] ?? 'asc')
        );
    }

    /** Relationships */
    public function contacts(): MorphMany
    {
        return $this->morphMany(Contact::class, 'contactable');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'evaluator_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function offerings(): HasManyThrough
    {
        return $this->hasManyThrough(Offering::class, Location::class);
    }

    /** Prunning of unactive profiles */
    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->minus(months: 6));
    }

    /** Prunning logic for relationships */
    public function prunning(): void {}
}
