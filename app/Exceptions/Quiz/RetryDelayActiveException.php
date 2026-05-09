<?php

namespace App\Exceptions\Quiz;

use Exception;

class RetryDelayActiveException extends Exception
{
    public function __construct(string $retryAt)
    {
        parent::__construct("You must wait until {$retryAt} before attempting this quiz again.");
    }
}
