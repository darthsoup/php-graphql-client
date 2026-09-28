<?php

namespace GraphQL;

use GraphQL\Util\StringLiteralFormatter;
use InvalidArgumentException;

final class Directive implements \Stringable
{
    /** @param array<array-key, mixed> $arguments */
    public function __construct(private readonly string $name, private readonly array $arguments = [])
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name)) {
            throw new InvalidArgumentException('Invalid GraphQL directive name');
        }
        foreach (array_keys($arguments) as $argumentName) {
            if (!is_string($argumentName) || !preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $argumentName)) {
                throw new InvalidArgumentException('Invalid GraphQL directive argument name');
            }
        }
    }

    public function __toString(): string
    {
        if ($this->arguments === []) {
            return '@' . $this->name;
        }

        $arguments = [];
        foreach ($this->arguments as $name => $value) {
            $arguments[] = $name . ': ' . StringLiteralFormatter::formatAnyValue($value);
        }

        return '@' . $this->name . '(' . implode(', ', $arguments) . ')';
    }
}
