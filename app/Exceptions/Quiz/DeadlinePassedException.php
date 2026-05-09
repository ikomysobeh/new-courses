<?php

namespace App\Exceptions\Quiz;

use Exception;

class DeadlinePassedException extends Exception
{
    public function __construct(string $message = 'The deadline for this quiz has passed.')
    {
        parent::__construct($message);
    }
}
