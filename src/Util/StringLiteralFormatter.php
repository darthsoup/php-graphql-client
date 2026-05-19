<?php

namespace GraphQL\Util;

use GraphQL\RawObject;

/**
 * Class StringLiteralFormatter
 *
 * @package GraphQL\Util
 */
class StringLiteralFormatter
{
    /**
     * @param string|int|float|bool|RawObject|null $value
     */
    public static function formatValueForRHS(string|int|float|bool|RawObject|null $value): string
    {
        if ($value instanceof RawObject) {
            return (string) $value;
        }

        if (is_string($value)) {
            if (!self::isVariable($value)) {
                $value = str_replace('"', '\\"', $value);
                if (str_contains($value, "\n")) {
                    $value = '"""' . $value . '"""';
                } else {
                    $value = "\"$value\"";
                }
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
        $arrString = '[';
        $first = true;
        foreach ($array as $element) {
            if ($first) {
                $first = false;
            } else {
                $arrString .= ', ';
            }

            if (is_array($element)) {
                $arrString .= self::formatArrayForGQLQuery($element);
            } elseif ($element instanceof RawObject || is_scalar($element) || $element === null) {
                $arrString .= self::formatValueForRHS($element);
            }
        }

        return $arrString . ']';
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
