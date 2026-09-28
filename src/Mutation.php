<?php

declare(strict_types=1);

namespace GraphQL;

class Mutation extends AbstractOperation
{
    protected const string OPERATION_TYPE = 'mutation';

    #[\Override]
    protected function getOperationType(): string
    {
        return static::OPERATION_TYPE;
    }
}
