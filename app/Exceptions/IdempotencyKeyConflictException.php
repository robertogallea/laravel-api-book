<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IdempotencyKeyConflictException extends HttpException
{
    public function __construct()
    {
        parent::__construct(409, 'This Idempotency-Key was already used with a different request payload.');
    }
}
