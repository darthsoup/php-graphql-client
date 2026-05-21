<?php

namespace GraphQL\Exception;

use RuntimeException;

class MethodNotSupportedException extends RuntimeException
{
    public function __construct(string $requestMethod)
    {
        parent::__construct('Method "' . $requestMethod . '" is currently unsupported by client.');
    }
}
