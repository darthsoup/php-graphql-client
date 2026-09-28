<?php

declare(strict_types=1);

namespace GraphQL\QueryBuilder;

use GraphQL\InlineFragment;
use GraphQL\AbstractOperation;
use GraphQL\RawObject;
use GraphQL\InputObject;
use GraphQL\VariableReference;
use GraphQL\FragmentSpread;

class QueryBuilder extends AbstractQueryBuilder
{
    #[\Override]
    public function selectField(
        string|QueryBuilderInterface|AbstractOperation|InlineFragment|FragmentSpread $selectedField
    ): static {
        return parent::selectField($selectedField);
    }

    /**
     * @param array<mixed>|string|int|float|bool|RawObject|InputObject|VariableReference|null $argumentValue
     */
    #[\Override]
    public function setArgument(
        string $argumentName,
        string|int|float|bool|array|RawObject|InputObject|VariableReference|null $argumentValue
    ): static {
        return parent::setArgument($argumentName, $argumentValue);
    }

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
