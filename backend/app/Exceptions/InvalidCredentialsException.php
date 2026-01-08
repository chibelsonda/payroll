<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCredentialsException extends HttpException
{
    public function __construct(string $message = 'Invalid credentials', int $statusCode = 401)
    {
        parent::__construct($statusCode, $message);
    }
}
