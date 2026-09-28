<?php

declare(strict_types=1);

namespace GraphQL;

use InvalidArgumentException;

final class FragmentSpread implements \Stringable
{
    /** @var array<int, Directive> */
    private array $directives = [];

    public function __construct(private readonly string $name)
    {
        if (!preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/', $name) || $name === 'on') {
            throw new InvalidArgumentException('Invalid GraphQL fragment name');
        }
    }

    /** @param array<int, mixed> $directives */
    public function setDirectives(array $directives): static
    {
        foreach ($directives as $directive) {
            if (!$directive instanceof Directive) {
                throw new InvalidArgumentException('Directives must be GraphQL\\Directive objects');
            }
        }

        $this->directives = $directives;

        return $this;
    }

    public function __toString(): string
    {
        return '...' . $this->name . ($this->directives === [] ? '' : ' ' . implode(' ', $this->directives));
    }
}
