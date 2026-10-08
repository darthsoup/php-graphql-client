<?php

declare(strict_types=1);

namespace GraphQL;

use InvalidArgumentException;

final class VariableReference implements \Stringable
{
    public function __construct(private readonly string $name)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name)) {
            throw new InvalidArgumentException('Invalid GraphQL variable name');
        }
    }

    public function __toString(): string
    {
        return '$' . $this->name;
    }
}
