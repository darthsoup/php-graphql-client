<?php

declare(strict_types=1);

namespace GraphQL\Support;

use GraphQL\InputObject;
use GraphQL\RawObject;
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
        return match (true) {
            $value instanceof RawObject,
            $value instanceof InputObject,
            $value instanceof VariableReference => (string) $value,
            is_string($value) => self::isVariable($value)
                ? $value
                : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => (string) $value,
        };
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
        return StringCaseConverter::upperCamelCase($stringValue);
    }

    public static function formatLowerCamelCase(string $stringValue): string
    {
        return StringCaseConverter::lowerCamelCase($stringValue);
    }
}
