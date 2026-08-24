<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Override;

class RoleAssigmentExcpetion extends Exception
{
    public function __construct(public readonly string $user_id, public readonly string $role, ?Throwable $previous = null)
    {
        return parent::__construct('exceptions.role_assigment_failed', 0, $previous);
    }

    public function context(): array
    {
        return ['role' => $this->role, 'user_id' => $this->user_id];
    }
}
