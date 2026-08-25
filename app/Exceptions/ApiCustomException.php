<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class ApiCustomException extends HttpException
{
    abstract public function context(): array;

    public function render($request)
    {
        return response()->json([
            'message' => __($this->getMessage(), $this->context()),
        ], $this->getStatusCode());
    }
}
