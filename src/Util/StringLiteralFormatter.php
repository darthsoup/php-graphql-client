<?php

declare(strict_types=1);

namespace GraphQL\Util;

use GraphQL\RawObject;
use GraphQL\InputObject;
use GraphQL\VariableReference;
use InvalidArgumentException;

class StringLiteralFormatter
{
    /**
     * @param string|int|float|bool|RawObject|InputObject|VariableReference|null $value
     */
    public static function formatValueForRHS(
        string|int|float|bool|RawObject|InputObject|VariableReference|null $value
    ): string {
        if ($value instanceof RawObject || $value instanceof InputObject || $value instanceof VariableReference) {
            return (string) $value;
        }

        if (is_string($value)) {
            if (!self::isVariable($value)) {
                $value = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = 'null';
        } else {
            $value = (string) $value;
        }

        return $value;
    }

    private static function isVariable(string $value): bool
    {
        return (bool) preg_match('/^\$[_A-Za-z][_0-9A-Za-z]*$/', $value);
    }

    /**
     * @param array<mixed> $array
     */
    public static function formatArrayForGQLQuery(array $array): string
    {
        $elements = [];
        foreach ($array as $element) {
            $elements[] = self::formatAnyValue($element);
        }

        return '[' . implode(', ', $elements) . ']';
    }

    public static function formatAnyValue(mixed $value): string
    {
        if (is_array($value)) {
            return self::formatArrayForGQLQuery($value);
        }

        if (
            is_scalar($value) || $value === null || $value instanceof RawObject
            || $value instanceof InputObject || $value instanceof VariableReference
        ) {
            return self::formatValueForRHS($value);
        }

        throw new InvalidArgumentException('Unsupported GraphQL input value');
    }

    public static function formatUpperCamelCase(string $stringValue): string
    {
        if (!str_contains($stringValue, '_')) {
            return ucfirst($stringValue);
        }

        return str_replace('_', '', ucwords($stringValue, '_'));
    }

    public static function formatLowerCamelCase(string $stringValue): string
    {
        return lcfirst(static::formatUpperCamelCase($stringValue));
    }
}
