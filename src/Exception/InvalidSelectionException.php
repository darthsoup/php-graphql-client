<?php

namespace GraphQL\Exception;

use InvalidArgumentException;

class InvalidSelectionException extends InvalidArgumentException
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
