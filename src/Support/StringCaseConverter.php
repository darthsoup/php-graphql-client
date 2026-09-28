<?php

declare(strict_types=1);

namespace GraphQL\Support;

final class StringCaseConverter
{
    public static function upperCamelCase(string $value): string
    {
        if (!str_contains($value, '_')) {
            return ucfirst($value);
        }

        return str_replace('_', '', ucwords($value, '_'));
    }

    public static function lowerCamelCase(string $value): string
    {
        return lcfirst(self::upperCamelCase($value));
    }
}
