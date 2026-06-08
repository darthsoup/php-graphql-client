<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\RawObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RawObject::class)]
final class RawObjectTest extends TestCase
{
    #[Test]
    public function testConvertToString(): void
    {
        // Test convert array
        $json = new RawObject('[1, 4, "y", 6.7]');
        $this->assertSame('[1, 4, "y", 6.7]', (string) $json);

        // Test convert graphql object
        $json = new RawObject('{arr: [1, "z"], str: "val", int: 1, obj: {x: "y"}}');
        $this->assertSame('{arr: [1, "z"], str: "val", int: 1, obj: {x: "y"}}', (string) $json);
    }
}
