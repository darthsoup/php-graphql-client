<?php

namespace GraphQL\Tests;

use GraphQL\Mutation;
use GraphQL\QueryBuilder\MutationBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MutationBuilderTest extends TestCase
{
    protected MutationBuilder $mutationBuilder;

    protected function setUp(): void
    {
        $this->mutationBuilder = new MutationBuilder('createObject');
    }

    #[Test]
    public function testConstruct()
    {
        $builder = new MutationBuilder('createObject');
        $builder->selectField('field_one');
        $this->assertInstanceOf(Mutation::class, $builder->getQuery());
        $this->assertInstanceOf(Mutation::class, $builder->getMutation());

        $expectedString = 'mutation {
createObject {
field_one
}
}';
        $this->assertEquals($expectedString, (string) $builder->getQuery());
        $this->assertEquals($expectedString, (string) $builder->getMutation());
    }
}
