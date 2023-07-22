<?php

namespace GraphQL\Util;

/**
 * Class StringLiteralFormatter
 *
 * @package GraphQL\Util
 */
class StringLiteralFormatter
{
    /**
     * Converts the value provided to the equivalent RHS value to be put in a file declaration
     *
     * @param string|int|float|bool $value
     */
    public static function formatValueForRHS($value): string
    {
        if (is_string($value)) {
            if (!static::isVariable($value)) {
                $value = str_replace('"', '\"', $value);
                if (strpos($value, "\n") !== false) {
                    $value = '"""' . $value . '"""';
                } else {
                    $value = "\"$value\"";
                }
            }
        } elseif (is_bool($value)) {
            if ($value) {
                $value = 'true';
            } else {
                $value = 'false';
            }
        } elseif ($value === null) {
            $value = 'null';
        } else {
            $value = (string) $value;
        }

        return $value;
    }

    /**
     * Treat string value as variable if it matches variable regex
     *
     *
     */
    private static function isVariable(string $value): bool {
        return preg_match('/^\$[_A-Za-z][_0-9A-Za-z]*$/', $value);
    }

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
            $arrString .= self::formatValueForRHS($element);
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
