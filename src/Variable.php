<?php

declare(strict_types=1);

namespace GraphQL;

use GraphQL\Support\StringLiteralFormatter;

class Variable implements \Stringable
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $type,
        protected readonly bool $required = false,
        protected readonly string|int|float|bool|null $defaultValue = null
    ) {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/D', $name)) {
            throw new \InvalidArgumentException('Invalid GraphQL variable name');
        }
        if (!preg_match('/^(?(DEFINE)(?<type>[_A-Za-z][_0-9A-Za-z]*|\[(?&type)!?\]))(?&type)!?$/D', $type)) {
            throw new \InvalidArgumentException('Invalid GraphQL variable type');
        }
    }

    public function __toString(): string
    {
        $varString = '$' . $this->name . ': ' . $this->type;
        if ($this->required) {
            $varString .= '!';
        } elseif ($this->defaultValue !== null) {
            $varString .= '=' . StringLiteralFormatter::formatValueForRHS($this->defaultValue);
        }

        return $varString;
    }
}
