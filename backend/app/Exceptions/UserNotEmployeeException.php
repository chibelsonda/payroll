<?php

namespace App\Exceptions;

use Exception;

class UserNotEmployeeException extends Exception
{
    public function __construct(string $message = 'User is not an employee', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
