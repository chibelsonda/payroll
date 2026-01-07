<?php

namespace App\Exceptions;

use Exception;

class EnrollmentAlreadyExistsException extends Exception
{
    public function __construct(string $message = 'Student is already enrolled in this subject', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
