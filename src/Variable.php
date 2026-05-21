<?php

namespace GraphQL;

use GraphQL\Util\StringLiteralFormatter;

class Variable implements \Stringable
{
    public function __construct(
        protected readonly string $name,
        protected readonly string $type,
        protected readonly bool $required = false,
        protected readonly string|int|float|bool|null $defaultValue = null
    ) {
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
