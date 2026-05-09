<?php

namespace App\Exceptions\Quiz;

use Exception;

class MaxAttemptsReachedException extends Exception
{
    public function __construct(string $message = 'You have reached the maximum number of attempts for this quiz.')
    {
        parent::__construct($message);
    }
}
