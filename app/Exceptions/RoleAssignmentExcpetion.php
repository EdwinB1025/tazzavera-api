<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Override;

class RoleAssignmentExcpetion extends ApiCustomException
{
    public function __construct(public readonly int $user_id, public readonly string $role, ?Throwable $previous = null)
    {
        parent::__construct(422, 'exceptions.role_assigment_failed', $previous);
    }

    public function context(): array
    {
        return ['role' => $this->role, 'user_id' => $this->user_id];
    }
}
