<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;


/**
 * @method static void creating(\Closure $callback)
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPublicUlid
{
    /**EDB 09/03/26: adding booted method to autogenerate ulid avoiding using it as PK in the model */
    protected static function bootHasPublicUlid(): void
    {
        static::creating(function ($user) {
            $user->ulid ??= (string) Str::ulid();
        });
    }

    /** Id field for the bidding of the auth middleware*/
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
