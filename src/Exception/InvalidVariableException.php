<?php

namespace GraphQL\Exception;

use InvalidArgumentException;

class InvalidVariableException extends InvalidArgumentException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
