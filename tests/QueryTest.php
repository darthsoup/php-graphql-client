<?php

namespace GraphQL\Tests;

use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidSelectionException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;
use GraphQL\Variable;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsUsingDeepClone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    /**
     * @return Query
     */
    #[Test]
    public function testConvertsToString()
    {
        $query = new Query('Object');
        $this->assertIsString((string) $query, 'Failed to convert query to string');

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[Depends('testConvertsToString')]
    public function testEmptyArguments(Query $query)
    {
        $this->assertStringNotContainsString("()", (string) $query, 'Query has empty arguments list');

        return $query;
    }

    #[Test]
    public function testQueryWithoutFieldName()
    {
        $query = new Query();

        $this->assertEquals(
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

        $this->assertEquals(
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
    public function testQueryWithAlias()
    {
        $query = (new Query('Object', 'ObjectAlias'))
            ->setSelectionSet([
                'one'
            ]);

        $this->assertEquals(
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
    public function testQueryWithSetAlias()
    {
        $query = (new Query('Object'))
            ->setAlias('ObjectAlias')
            ->setSelectionSet([
                'one'
            ]);

        $this->assertEquals(
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
    public function testQueryWithOperationName()
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject');
        $this->assertEquals(
'query retrieveObject {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithoutFieldName')]
    #[Depends('testQueryWithOperationName')]
    public function testQueryWithOperationNameAndOperationType()
    {
        $query = (new Query())
            ->setOperationName('retrieveObject')
            ->setSelectionSet([new Query('Object')]);
        $this->assertEquals(
            'query retrieveObject {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithOperationName')]
    public function testQueryWithOperationNameInSecondLevelDoesNothing()
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject')
            ->setSelectionSet([(new Query('Nested'))->setOperationName('opName')]);
        $this->assertEquals(
            'query retrieveObject {
Object {
Nested
}
}',
            (string) $query
        );
    }

    #[Test]
    public function testSetVariablesWithoutVariableObjects()
    {
        $this->expectException(InvalidVariableException::class);
        (new Query('Object'))->setVariables(['one', 'two']);
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithOneVariable()
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String')]);
        $this->assertEquals(
            'query($var: String) {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testQueryWithOneVariable')]
    public function testQueryWithMultipleVariables()
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)]);
        $this->assertEquals(
            'query($var: String $intVar: Int=4) {
Object
}',
            (string) $query
        );
    }

    #[Test]
    #[Depends('testConvertsToString')]
    public function testQueryWithVariablesInSecondLevelDoesNothing()
    {
        $query = (new Query('Object'))
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)])
            ->setSelectionSet([(new Query('Nested'))])
            ->setVariables([new Variable('var', 'String'), new Variable('intVar', 'Int', false, 4)]);
        $this->assertEquals(
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
    public function testQueryWithOperationNameAndVariables()
    {
        $query = (new Query('Object'))
            ->setOperationName('retrieveObject')
            ->setVariables([new Variable('var', 'String')]);
        $this->assertEquals(
            'query retrieveObject($var: String) {
Object
}',
            (string) $query
        );
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testEmptyQuery(Query $query)
    {
        $this->assertEquals(
            "query {
Object
}",
            (string) $query,
            'Incorrect empty query string'
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArgumentWithoutName(Query $query)
    {
        $this->expectException(ArgumentException::class);
        $query->setArguments(['val']);

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testStringArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => 'value']);
        $this->assertEquals(
            "query {
Object(arg1: \"value\")
}",
            (string) $query,
            'Query has improperly formatted parameter list'
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testIntegerArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => 23]);
        $this->assertEquals(
            "query {
Object(arg1: 23)
}",
            (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testBooleanArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => true]);
        $this->assertEquals(
            "query {
Object(arg1: true)
}",
            (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testNullArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => null]);
        $this->assertEquals(
            "query {
Object(arg1: null)
}"
            , (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArrayIntegerArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => [1, 2, 3]]);
        $this->assertEquals(
            "query {
Object(arg1: [1, 2, 3])
}",
            (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testJsonObjectArgumentValue(Query $query)
    {
        $query->setArguments(['obj' => new RawObject('{json_string_array: ["json value"]}')]);
        $this->assertEquals(
            "query {
Object(obj: {json_string_array: [\"json value\"]})
}"
            , (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyArguments')]
    public function testArrayStringArgumentValue(Query $query)
    {
        $query->setArguments(['arg1' => ['one', 'two', 'three']]);
        $this->assertEquals(
            "query {
Object(arg1: [\"one\", \"two\", \"three\"])
}",
            (string) $query
        );

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testStringArgumentValue')]
    #[Depends('testIntegerArgumentValue')]
    #[Depends('testBooleanArgumentValue')]
    public function testTwoOrMoreArguments(Query $query)
    {
        $query->setArguments(['arg1' => 'val1', 'arg2' => 2, 'arg3' => true]);
        $this->assertEquals(
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
    public function testStringWrappingWorks()
    {
        // TODO: Remove this in v1.0 release
        $queryWrapped = new Query('Object');
        $queryWrapped->setArguments(['arg1' => '"val"']);

        $queryNotWrapped = new Query('Object');
        $queryNotWrapped->setArguments(['arg1' => 'val']);

        $this->assertEquals((string) $queryWrapped, (string) $queryWrapped);
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testSingleSelectionField(Query $query)
    {
        $query->setSelectionSet(['field1']);
        $this->assertEquals(
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

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testTwoOrMoreSelectionFields(Query $query)
    {
        $query->setSelectionSet(['field1', 'field2']);
        $this->assertEquals(
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

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testSelectNonStringValues(Query $query)
    {
        $this->expectException(InvalidSelectionException::class);
        $query->setSelectionSet([true, 1.5]);

        return $query;
    }

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testEmptyQuery')]
    public function testOneLevelQuery(Query $query)
    {
        $query->setSelectionSet(['field1', 'field2']);
        $query->setArguments(['arg1' => 'val1', 'arg2' => 'val2']);
        $this->assertEquals(
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

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testOneLevelQuery')]
    public function testTwoLevelQueryDoesNotContainWordQuery(Query $query)
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

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testTwoLevelQueryDoesNotContainWordQuery')]
    public function testTwoLevelQuery(Query $query)
    {
        $query->setSelectionSet(
            [
                'field1',
                'field2',
                (new Query('Object2'))
                    ->setSelectionSet(['field3'])
            ]
        );
        $this->assertEquals(
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

    /**
     * @return Query
     */
    #[Test]
    #[DependsUsingDeepClone('testTwoLevelQueryDoesNotContainWordQuery')]
    public function testTwoLevelQueryWithInlineFragment(Query $query)
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
        $this->assertEquals(
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
