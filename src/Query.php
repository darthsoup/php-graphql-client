<?php

declare(strict_types=1);

namespace GraphQL;

class Query extends AbstractOperation
{
    protected const string OPERATION_TYPE = 'query';

    #[\Override]
    protected function getOperationType(): string
    {
        return static::OPERATION_TYPE;
    }
}
