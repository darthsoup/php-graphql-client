<?php

declare(strict_types=1);

namespace GraphQL;

use InvalidArgumentException;

final class FragmentDefinition implements \Stringable
{
    use FieldTrait;

    public function __construct(private readonly string $name, private readonly string $typeName)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === 'on') {
            throw new InvalidArgumentException('Invalid GraphQL fragment name');
        }
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $typeName)) {
            throw new InvalidArgumentException('Invalid GraphQL fragment type name');
        }
        $this->selectionSet = [];
    }

    public function __toString(): string
    {
        return 'fragment ' . $this->name . ' on ' . $this->typeName
            . $this->constructDirectives() . $this->constructSelectionSet();
    }
}
