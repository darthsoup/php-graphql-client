<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidSelectionException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;
use GraphQL\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsUsingDeepClone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Query::class)]
final class QueryTest extends TestCase
{
    #[Test]
    public function testConvertsToString(): Query
    {
        $query = new Query('Object');
        $this->assertIsString((string) $query, 'Failed to convert query to string');

        return $query;
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testEmptyArguments(Query $query): Query
    {
        $this->assertStringNotContainsString("()", (string) $query, 'Query has empty arguments list');

        return $query;
    }

    #[Test]
    public function testQueryWithoutFieldName(): void
    {
        $query = new Query();

        $this->assertSame(
            "query",
            (string) $query
        );

        $query->setSelectionSet(
            [
                (new Query('Object'))
                    ->setSelectionSet(['one']),
                (new Query('Another'))
                    ->setSelectionSet(['two'])
            ]
        );

        $this->assertSame(
            "query {
Object {
one
}
Another {
two
}
}",
            (string) $query
        );
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithAlias(): void
    {
        $query = (new Query('Object', 'ObjectAlias'))
            ->setSelectionSet([
                'one'
            ]);

        $this->assertSame(
            "query {
ObjectAlias: Object {
one
}
}",
            (string) $query
        );
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithSetAlias(): void
    {
        $query = (new Query('Object'))
            ->setAlias('ObjectAlias')
            ->setSelectionSet([
                'one'
            ]);

        $this->assertSame(
            "query {
ObjectAlias: Object {
one
}
}",
            (string) $query
        );
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithOperationName(): void
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject');
        $this->assertSame(
            'query retrieveObject {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithoutFieldName')]
    #[Depends('testQueryWithOperationName')]
    public function testQueryWithOperationNameAndOperationType(): void
    {
        $query = (new Query())
            ->setOperationName('retrieveObject')
            ->setSelectionSet([new Query('Object')]);
        $this->assertSame(
            'query retrieveObject {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithOperationName')]
    public function testQueryWithOperationNameInSecondLevelDoesNothing(): void
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject')
            ->setSelectionSet([(new Query('Nested'))->setOperationName('opName')]);
        $this->assertSame(
            'query retrieveObject {
Object {
Nested
}
}',
            (string) $query
        );
    }

    #[Test]
    public function testSetVariablesWithoutVariableObjects(): void
    {
        $this->expectException(InvalidVariableException::class);
        (new Query('Object'))->setVariables(['one', 'two']);
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithOneVariable(): void
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String')]);
        $this->assertSame(
            'query($var: String) {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithOneVariable')]
    public function testQueryWithMultipleVariables(): void
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)]);
        $this->assertSame(
            'query($var: String $intVar: Int=4) {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithVariablesInSecondLevelDoesNothing(): void
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)])
            ->setSelectionSet([(new Query('Nested'))])
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)]);
        $this->assertSame(
            'query($var: String $intVar: Int=4) {
Object {
Nested
}
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithMultipleVariables')]
    #[Depends('testQueryWithOperationName')]
    public function testQueryWithOperationNameAndVariables(): void
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject')
            ->setVariables([new Variable('var', 'String')]);
        $this->assertSame(
            'query retrieveObject($var: String) {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testEmptyQuery(Query $query): Query
    {
        $this->assertSame(
            "query {
Object
}",
            (string) $query,
            'Incorrect empty query string'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArgumentWithoutName(Query $query): Query
    {
        $this->expectException(ArgumentException::class);
        $query->setArguments(['val']);

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testStringArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => 'value']);
        $this->assertSame(
            "query {
Object(arg1: \"value\")
}",
            (string) $query,
            'Query has improperly formatted parameter list'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testIntegerArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => 23]);
        $this->assertSame(
            "query {
Object(arg1: 23)
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testBooleanArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => true]);
        $this->assertSame(
            "query {
Object(arg1: true)
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testNullArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => null]);
        $this->assertSame(
            "query {
Object(arg1: null)
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArrayIntegerArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => [1, 2, 3]]);
        $this->assertSame(
            "query {
Object(arg1: [1, 2, 3])
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testJsonObjectArgumentValue(Query $query): Query
    {
        $query->setArguments(['obj' => new RawObject('{json_string_array: ["json value"]}')]);
        $this->assertSame(
            "query {
Object(obj: {json_string_array: [\"json value\"]})
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArrayStringArgumentValue(Query $query): Query
    {
        $query->setArguments(['arg1' => ['one', 'two', 'three']]);
        $this->assertSame(
            "query {
Object(arg1: [\"one\", \"two\", \"three\"])
}",
            (string) $query
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testStringArgumentValue')]
    #[Depends('testIntegerArgumentValue')]
    #[Depends('testBooleanArgumentValue')]
    public function testTwoOrMoreArguments(Query $query): Query
    {
        $query->setArguments(['arg1' => 'val1', 'arg2' => 2, 'arg3' => true]);
        $this->assertSame(
            "query {
Object(arg1: \"val1\" arg2: 2 arg3: true)
}",
            (string) $query,
            'Query has improperly formatted parameter list'
        );

        return $query;
    }

    #[Test]
    #[Depends('testStringArgumentValue')]
    public function testStringWrappingWorks(): void
    {
        // TODO: Remove this in v1.0 release
        $queryWrapped = new Query('Object');
        $queryWrapped->setArguments(['arg1' => '"val"']);

        $queryNotWrapped = new Query('Object');
        $queryNotWrapped->setArguments(['arg1' => 'val']);

        $this->assertSame((string) $queryWrapped, (string) $queryWrapped);
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testSingleSelectionField(Query $query): Query
    {
        $query->setSelectionSet(['field1']);
        $this->assertSame(
            "query {
Object {
field1
}
}",
            (string) $query,
            'Query has improperly formatted selection set'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testTwoOrMoreSelectionFields(Query $query): Query
    {
        $query->setSelectionSet(['field1', 'field2']);
        $this->assertSame(
            "query {
Object {
field1
field2
}
}",
            (string) $query,
            'Query has improperly formatted selection set'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testSelectNonStringValues(Query $query): Query
    {
        $this->expectException(InvalidSelectionException::class);
        $query->setSelectionSet([true, 1.5]);

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testOneLevelQuery(Query $query): Query
    {
        $query->setSelectionSet(['field1', 'field2']);
        $query->setArguments(['arg1' => 'val1', 'arg2' => 'val2']);
        $this->assertSame(
            "query {
Object(arg1: \"val1\" arg2: \"val2\") {
field1
field2
}
}",
            (string) $query,
            'One level query not formatted correctly'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testOneLevelQuery')]
    public function testTwoLevelQueryDoesNotContainWordQuery(Query $query): Query
    {
        $query->setSelectionSet(
            [
                'field1',
                'field2',
                (new Query('Object2'))
                    ->setSelectionSet(['field3'])
            ]
        );
        $this->assertStringNotContainsString(
            "\nquery {",
            (string) $query,
            'Nested query contains "query" word'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testTwoLevelQueryDoesNotContainWordQuery')]
    public function testTwoLevelQuery(Query $query): Query
    {
        $query->setSelectionSet(
            [
                'field1',
                'field2',
                (new Query('Object2'))
                    ->setSelectionSet(['field3'])
            ]
        );
        $this->assertSame(
            "query {
Object(arg1: \"val1\" arg2: \"val2\") {
field1
field2
Object2 {
field3
}
}
}",
            (string) $query,
            'Two level query not formatted correctly'
        );

        return $query;
    }

    #[Test]
    #[DependsUsingDeepClone('testTwoLevelQueryDoesNotContainWordQuery')]
    public function testTwoLevelQueryWithInlineFragment(Query $query): Query
    {
        $query->setSelectionSet(
            [
                'field1',
                (new InlineFragment('Object'))
                    ->setSelectionSet(
                        [
                            'fragment_field1',
                            'fragment_field2',
                        ]
                    ),
            ]
        );
        $this->assertSame(
            'query {
Object(arg1: "val1" arg2: "val2") {
field1
... on Object {
fragment_field1
fragment_field2
}
}
}',
            (string) $query
        );

        return $query;
    }
}
