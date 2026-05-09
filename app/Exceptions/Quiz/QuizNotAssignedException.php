<?php

namespace App\Exceptions\Quiz;

use Exception;

class QuizNotAssignedException extends Exception
{
    public function __construct(string $message = 'You are not assigned to this quiz.')
    {
        parent::__construct($message);
    }
}
