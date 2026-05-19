<?php

namespace GraphQL;

use GraphQL\Util\StringLiteralFormatter;

/**
 * Class Variable
 *
 * @package GraphQL
 */
class Variable
{
    protected string $name;

    protected string $type;

    protected bool $required;

    public function __construct(
        string $name,
        string $type,
        bool $isRequired = false,
        protected string|int|float|bool|null $defaultValue = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->required = $isRequired;
    }

    public function __toString(): string
    {
        $varString = "\$$this->name: $this->type";
        if ($this->required) {
            $varString .= '!';
        } elseif ($this->defaultValue !== null) {
            $varString .= '=' . StringLiteralFormatter::formatValueForRHS($this->defaultValue);
        }

        return $varString;
    }
}
