<?php

namespace App\Contracts;

use App\Models\User;

interface RegistrationTokenServiceContract
{
    /**
     * Tokens that sign the user in on the front client, or null when they cannot be issued.
     *
     * @return array{token_type: string, expires_in: int, access_token: string, refresh_token: string}|null
     */
    public function issueFor(User $user): ?array;
}
