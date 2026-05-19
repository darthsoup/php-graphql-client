<?php

namespace GraphQL\QueryBuilder;

use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;

/**
 * Class QueryBuilder
 *
 * @package GraphQL
 */
class QueryBuilder extends AbstractQueryBuilder
{
    /**
     * @return static
     */
    #[\Override]
    public function selectField(string|QueryBuilderInterface|Query|InlineFragment $selectedField): static
    {
        return parent::selectField($selectedField);
    }

    /**
     * @param array<mixed>|string|int|float|bool|RawObject $argumentValue
     *
     * @return static
     */
    #[\Override]
    public function setArgument(string $argumentName, string|int|float|bool|array|RawObject $argumentValue): static
    {
        return parent::setArgument($argumentName, $argumentValue);
    }

    /**
     * @return static
     */
    #[\Override]
    public function setVariable(
        string $name,
        string $type,
        bool $isRequired = false,
        string|int|float|bool|null $defaultValue = null
    ): static {
        return parent::setVariable($name, $type, $isRequired, $defaultValue);
    }
}
