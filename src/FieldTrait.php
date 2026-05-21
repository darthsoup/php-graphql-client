<?php

namespace GraphQL;

use GraphQL\Exception\InvalidSelectionException;

trait FieldTrait
{
    /** @var array<int, string|Query|InlineFragment> */
    protected array $selectionSet;

    /**
     * @param array<int, string|Query|InlineFragment> $selectionSet
     *
     * @throws InvalidSelectionException
     */
    public function setSelectionSet(array $selectionSet): static
    {
        /** @var array<int, mixed> $selectionItems */
        $selectionItems = $selectionSet;
        $nonStringsFields = array_filter(
            $selectionItems,
            fn($element) => !is_string($element) && !$element instanceof Query && !$element instanceof InlineFragment
        );

        if (!empty($nonStringsFields)) {
            throw new InvalidSelectionException(
                'One or more of the selection fields provided is not of type string or Query'
            );
        }

        $this->selectionSet = $selectionSet;

        return $this;
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

            if ($attribute instanceof Query) {
                $attribute->setAsNested();
            }

            $attributesString .= $attribute;
        }

        return $attributesString . PHP_EOL . '}';
    }

    /** @return array<int, string|Query|InlineFragment> */
    public function getSelectionSet(): array
    {
        return $this->selectionSet;
    }
}
