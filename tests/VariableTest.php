<?php

namespace GraphQL\Tests;

use GraphQL\Variable;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VariableTest extends TestCase
{
    #[Test]
    public function testCreateVariable()
    {
        $variable = new Variable('var', 'String');
        $this->assertEquals('$var: String', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateVariable')]
    public function testCreateRequiredVariable()
    {
        $variable = new Variable('var', 'String', true);
        $this->assertEquals('$var: String!', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateRequiredVariable')]
    public function testRequiredVariableWithDefaultValueDoesNothing()
    {
        $variable = new Variable('var', 'String', true, 'def');
        $this->assertEquals('$var: String!', (string) $variable);
    }

    #[Test]
    #[Depends('testCreateVariable')]
    public function testOptionalVariableWithDefaultValue()
    {
        $variable = new Variable('var', 'String', false, 'def');
        $this->assertEquals('$var: String="def"', (string) $variable);

        $variable = new Variable('var', 'String', false, '4');
        $this->assertEquals('$var: String="4"', (string) $variable);

        $variable = new Variable('var', 'Int', false, 4);
        $this->assertEquals('$var: Int=4', (string) $variable);

        $variable = new Variable('var', 'Boolean', false, true);
        $this->assertEquals('$var: Boolean=true', (string) $variable);
    }
}