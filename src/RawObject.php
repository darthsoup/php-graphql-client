<?php

namespace GraphQL;

class RawObject implements \Stringable
{
    public function __construct(protected readonly string $objectString)
    {
    }

    public function __toString(): string
    {
        return $this->objectString;
    }
}
