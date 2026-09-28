<?php

declare(strict_types=1);

namespace GraphQL;

use GraphQL\Util\StringLiteralFormatter;
use InvalidArgumentException;

final class InputObject implements \Stringable
{
    /** @param array<array-key, mixed> $fields */
    public function __construct(private readonly array $fields)
    {
        foreach (array_keys($fields) as $name) {
            if (!is_string($name) || !preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name)) {
                throw new InvalidArgumentException('Invalid GraphQL input object field name');
            }
        }
    }

    public function __toString(): string
    {
        $fields = [];
        foreach ($this->fields as $name => $value) {
            $fields[] = $name . ': ' . StringLiteralFormatter::formatAnyValue($value);
        }

        return '{' . implode(', ', $fields) . '}';
    }
}
