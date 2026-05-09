<?php

namespace App\Exceptions\Quiz;

use Exception;

class QuizNotAvailableException extends Exception
{
    public function __construct(string $message = 'This quiz is not available.')
    {
        parent::__construct($message);
    }
}
