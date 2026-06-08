<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InlineFragment::class)]
final class InlineFragmentTest extends TestCase
{
    #[Test]
    public function testConvertToString(): void
    {
        $fragment = new InlineFragment('Test');
        $fragment->setSelectionSet(
            [
                'field1',
                'field2',
            ]
        );

        $this->assertSame(
            '... on Test {
field1
field2
}',
            (string) $fragment
        );
    }

    #[Test]
    public function testConvertNestedFragmentToString(): void
    {
        $fragment = new InlineFragment('Test');
        $fragment->setSelectionSet(
            [
                'field1',
                'field2',
                (new Query('sub_field'))
                    ->setArguments(
                        [
                            'first' => 5
                        ]
                    )
                    ->setSelectionSet(
                        [
                            'sub_field3',
                            (new InlineFragment('Nested'))
                                ->setSelectionSet(
                                    [
                                        'another_field'
                                    ]
                                ),
                        ]
                    )
            ]
        );

        $this->assertSame(
            '... on Test {
field1
field2
sub_field(first: 5) {
sub_field3
... on Nested {
another_field
}
}
}',
            (string) $fragment
        );
    }

    #[Test]
    public function testConvertQueryBuilderToString(): void
    {
        $queryBuilder = new QueryBuilder();

        $fragment = new InlineFragment('Test', $queryBuilder);
        $queryBuilder->selectField('field1');
        $queryBuilder->selectField('field2');

        $this->assertSame(
            '... on Test {
field1
field2
}',
            (string) $fragment
        );
    }
}
