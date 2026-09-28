<?php

declare(strict_types=1);

namespace GraphQL;

use GraphQL\Exception\InvalidSelectionException;

trait FieldTrait
{
    /** @var array<int, string|AbstractOperation|InlineFragment|FragmentSpread> */
    protected array $selectionSet;

    /** @var array<int, Directive> */
    protected array $directives = [];

    /**
     * @param array<int, string|AbstractOperation|InlineFragment|FragmentSpread> $selectionSet
     *
     * @throws InvalidSelectionException
     */
    public function setSelectionSet(array $selectionSet): static
    {
        /** @var array<int, mixed> $selectionItems */
        $selectionItems = $selectionSet;
        $nonStringsFields = array_filter(
            $selectionItems,
            fn ($element) => !is_string($element) && !$element instanceof AbstractOperation
                && !$element instanceof InlineFragment && !$element instanceof FragmentSpread
        );

        if (!empty($nonStringsFields)) {
            throw new InvalidSelectionException(
                'One or more of the selection fields provided is not of type string or Query'
            );
        }

        $this->selectionSet = $selectionSet;

        return $this;
    }

    /** @param array<int, mixed> $directives */
    public function setDirectives(array $directives): static
    {
        foreach ($directives as $directive) {
            if (!$directive instanceof Directive) {
                throw new \InvalidArgumentException('Directives must be GraphQL\\Directive objects');
            }
        }

        $this->directives = $directives;

        return $this;
    }

    protected function constructDirectives(): string
    {
        return $this->directives === [] ? '' : ' ' . implode(' ', $this->directives);
    }

    protected function constructSelectionSet(): string
    {
        if (empty($this->selectionSet)) {
            return '';
        }

        $attributesString = ' {' . PHP_EOL;
        $first = true;
        foreach ($this->selectionSet as $attribute) {
            if ($first) {
                $first = false;
            } else {
                $attributesString .= PHP_EOL;
            }

            if ($attribute instanceof AbstractOperation) {
                $attributesString .= $attribute->toFieldString();
            } else {
                $attributesString .= $attribute;
            }
        }

        return $attributesString . PHP_EOL . '}';
    }

    /** @return array<int, string|AbstractOperation|InlineFragment|FragmentSpread> */
    public function getSelectionSet(): array
    {
        return $this->selectionSet;
    }
}
