<?php

namespace App\Exceptions;

use Exception;

class UserNotStudentException extends Exception
{
    public function __construct(string $message = 'User is not a student', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
