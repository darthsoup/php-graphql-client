<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit\QueryBuilder;

use GraphQL\Mutation;
use GraphQL\QueryBuilder\MutationBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MutationBuilder::class)]
final class MutationBuilderTest extends TestCase
{
    protected MutationBuilder $mutationBuilder;

    protected function setUp(): void
    {
        $this->mutationBuilder = new MutationBuilder('createObject');
    }

    #[Test]
    public function testConstruct(): void
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
        $this->assertSame($expectedString, (string) $builder->getQuery());
        $this->assertSame($expectedString, (string) $builder->getMutation());
    }
}
