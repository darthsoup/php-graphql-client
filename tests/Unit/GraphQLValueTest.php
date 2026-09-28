<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Directive;
use GraphQL\FragmentSpread;
use GraphQL\InputObject;
use GraphQL\VariableReference;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Directive::class)]
#[CoversClass(FragmentSpread::class)]
#[CoversClass(InputObject::class)]
#[CoversClass(VariableReference::class)]
final class GraphQLValueTest extends TestCase
{
    #[Test]
    public function testDirectiveAndFragmentSpread(): void
    {
        $spread = (new FragmentSpread('Fields'))
            ->setDirectives([new Directive('skip', ['if' => new VariableReference('hidden')])]);

        self::assertSame('...Fields @skip(if: $hidden)', (string) $spread);
    }

    #[Test]
    public function testInputObjectRejectsInvalidFieldName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InputObject(['bad name' => true]);
    }

    #[Test]
    public function testVariableReferenceRejectsInvalidName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VariableReference('1bad');
    }
}
