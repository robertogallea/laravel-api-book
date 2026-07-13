<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IdempotencyKeyInProgressException extends HttpException
{
    public function __construct()
    {
        parent::__construct(409, 'A request with this Idempotency-Key is still being processed.');
    }
}
