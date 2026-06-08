<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Mutation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mutation::class)]
final class MutationTest extends TestCase
{
    #[Test]
    public function testMutationWithoutOperationType(): void
    {
        $mutation = new Mutation('createObject');

        $this->assertSame(
            'mutation {
createObject
}',
            (string) $mutation
        );
    }

    #[Test]
    public function testMutationWithOperationType(): void
    {
        $mutation = new Mutation();
        $mutation
            ->setSelectionSet(
                [
                    (new Mutation('createObject'))
                        ->setArguments(['name' => 'TestObject'])
                ]
            );

        $this->assertSame(
            'mutation {
createObject(name: "TestObject")
}',
            (string) $mutation
        );
    }

    #[Test]
    public function testMutationWithoutSelectedFields(): void
    {
        $mutation = (new Mutation('createObject'))
            ->setArguments(['name' => 'TestObject', 'type' => 'TestType']);
        $this->assertSame(
            'mutation {
createObject(name: "TestObject" type: "TestType")
}',
            (string) $mutation
        );
    }

    #[Test]
    public function testMutationWithFields(): void
    {
        $mutation = (new Mutation('createObject'))
            ->setSelectionSet(
                [
                    'fieldOne',
                    'fieldTwo',
                ]
            );

        $this->assertSame(
            'mutation {
createObject {
fieldOne
fieldTwo
}
}',
            (string) $mutation
        );
    }

    #[Test]
    public function testMutationWithArgumentsAndFields(): void
    {
        $mutation = (new Mutation('createObject'))
            ->setSelectionSet(
                [
                    'fieldOne',
                    'fieldTwo',
                ]
            )->setArguments(
                [
                    'argOne' => 1,
                    'argTwo' => 'val'
                ]
            );

        $this->assertSame(
            'mutation {
createObject(argOne: 1 argTwo: "val") {
fieldOne
fieldTwo
}
}',
            (string) $mutation
        );
    }
}
