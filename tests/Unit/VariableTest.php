<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Variable::class)]
final class VariableTest extends TestCase
{
    #[Test]
    public function testCreateVariable(): void
    {
        $variable = new Variable('var', 'String');
        $this->assertSame('$var: String', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateVariable')]
    public function testCreateRequiredVariable(): void
    {
        $variable = new Variable('var', 'String', true);
        $this->assertSame('$var: String!', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateRequiredVariable')]
    public function testRequiredVariableWithDefaultValueDoesNothing(): void
    {
        $variable = new Variable('var', 'String', true, 'def');
        $this->assertSame('$var: String!', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateVariable')]
    public function testOptionalVariableWithDefaultValue(): void
    {
        $variable = new Variable('var', 'String', false, 'def');
        $this->assertSame('$var: String="def"', (string) $variable);

        $variable = new Variable('var', 'String', false, '4');
        $this->assertSame('$var: String="4"', (string) $variable);

        $variable = new Variable('var', 'Int', false, 4);
        $this->assertSame('$var: Int=4', (string) $variable);

        $variable = new Variable('var', 'Boolean', false, true);
        $this->assertSame('$var: Boolean=true', (string) $variable);
    }
}
