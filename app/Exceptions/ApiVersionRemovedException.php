<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiVersionRemovedException extends HttpException
{
    public function __construct(string $version)
    {
        parent::__construct(410, "API version \"{$version}\" has been removed.");
    }
}
