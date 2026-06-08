<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit\Util;

use GraphQL\Util\StringLiteralFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(StringLiteralFormatter::class)]
final class StringLiteralFormatterTest extends TestCase
{
    #[Test]
    public function testFormatForClassRHSValue(): void
    {
        // Null test
        $nullString = StringLiteralFormatter::formatValueForRHS(null);
        $this->assertSame('null', $nullString);

        // String tests
        $emptyString = StringLiteralFormatter::formatValueForRHS('');
        $this->assertSame('""', $emptyString);

        $formattedString = StringLiteralFormatter::formatValueForRHS('someString');
        $this->assertSame('"someString"', $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS('"quotedString"');
        $this->assertSame('"\"quotedString\""', $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS("\"quotedString\"");
        $this->assertSame('"\"quotedString\""', $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS('\'singleQuotes\'');
        $this->assertSame('"\'singleQuotes\'"', $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS("with \n newlines");
        $this->assertSame("\"\"\"with \n newlines\"\"\"", $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS('$var');
        $this->assertSame('$var', $formattedString);

        $formattedString = StringLiteralFormatter::formatValueForRHS('$400');
        $this->assertSame('"$400"', $formattedString);

        // Integer tests
        $integerString = StringLiteralFormatter::formatValueForRHS(25);
        $this->assertSame('25', $integerString);

        // Float tests
        $floatString = StringLiteralFormatter::formatValueForRHS(123.123);
        $this->assertSame('123.123', $floatString);

        // Bool tests
        $stringTrue = StringLiteralFormatter::formatValueForRHS(true);
        $this->assertSame('true', $stringTrue);

        $stringFalse = StringLiteralFormatter::formatValueForRHS(false);
        $this->assertSame('false', $stringFalse);
    }

    #[Test]
    public function testFormatArrayForGQLQuery(): void
    {
        $emptyArray = [];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($emptyArray);
        $this->assertSame('[]', $stringArray);

        $oneValueArray = [1];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($oneValueArray);
        $this->assertSame('[1]', $stringArray);

        $twoValueArray = [1, 2];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($twoValueArray);
        $this->assertSame('[1, 2]', $stringArray);

        $stringArray = ['one', 'two'];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($stringArray);
        $this->assertSame('["one", "two"]', $stringArray);

        $booleanArray = [true, false];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($booleanArray);
        $this->assertSame('[true, false]', $stringArray);

        $floatArray = [1.1, 2.2];
        $stringArray = StringLiteralFormatter::formatArrayForGQLQuery($floatArray);
        $this->assertSame('[1.1, 2.2]', $stringArray);
    }

    #[Test]
    public function testFormatUpperCamelCase(): void
    {
        $snakeCase = 'some_snake_case';
        $camelCase = StringLiteralFormatter::formatUpperCamelCase($snakeCase);
        $this->assertSame('SomeSnakeCase', $camelCase);

        $nonSnakeCase = 'somenonSnakeCase';
        $camelCase = StringLiteralFormatter::formatUpperCamelCase($nonSnakeCase);
        $this->assertSame('SomenonSnakeCase', $camelCase);
    }

    #[Test]
    public function testFormatLowerCamelCase(): void
    {
        $snakeCase = 'some_snake_case';
        $camelCase = StringLiteralFormatter::formatLowerCamelCase($snakeCase);
        $this->assertSame('someSnakeCase', $camelCase);

        $nonSnakeCase = 'somenonSnakeCase';
        $camelCase = StringLiteralFormatter::formatLowerCamelCase($nonSnakeCase);
        $this->assertSame('somenonSnakeCase', $camelCase);
    }
}
