<?php

namespace App\Models\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Client as FirstClient;

class Client extends FirstClient
{
    /** EDB 10/08/26: name of the first-party front client (FrontClientSeeder) */
    public const FRONT = 'tazavera-front';

    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty();
    }

    /**Model Scopes */

    /**
     * EDB 10/08/26: the first-party front client, also used to sign a new user in after registration
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    #[Scope]
    protected function front(Builder $query): void
    {
        $query->where('name', self::FRONT);
    }
}
