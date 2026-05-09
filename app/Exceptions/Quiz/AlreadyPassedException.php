<?php

namespace App\Exceptions\Quiz;

use Exception;

class AlreadyPassedException extends Exception
{
    public function __construct(string $message = 'You have already passed this quiz.')
    {
        parent::__construct($message);
    }
}
