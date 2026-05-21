<?php

namespace GraphQL\Exception;

use RuntimeException;

class AwsRegionNotSetException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('AWS region not set.');
    }
}
