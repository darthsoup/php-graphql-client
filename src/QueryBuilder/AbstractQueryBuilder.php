<?php

namespace GraphQL\QueryBuilder;

use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;
use GraphQL\Variable;

abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    protected Query $query;

    /** @var array<int, Variable> */
    private array $variables;

    /** @var array<int, string|QueryBuilderInterface|Query|InlineFragment> */
    private array $selectionSet;

    /** @var array<string, string|int|float|bool|array<mixed>|RawObject> */
    private array $argumentsList;

    public function __construct(string $queryObject = '', string $alias = '')
    {
        $this->query = new Query($queryObject, $alias);
        $this->variables = [];
        $this->selectionSet = [];
        $this->argumentsList = [];
    }

    public function setAlias(string $alias): static
    {
        $this->query->setAlias($alias);

        return $this;
    }

    public function getQuery(): Query
    {
        $selectionSet = [];
        foreach ($this->selectionSet as $field) {
            $selectionSet[] = $field instanceof QueryBuilderInterface ? $field->getQuery() : $field;
        }

        $this->query->setVariables($this->variables);
        $this->query->setArguments($this->argumentsList);
        $this->query->setSelectionSet($selectionSet);

        return $this->query;
    }

    protected function selectField(string|QueryBuilderInterface|Query|InlineFragment $selectedField): static
    {
        $this->selectionSet[] = $selectedField;

        return $this;
    }

    /**
     * @param array<mixed>|string|int|float|bool|RawObject $argumentValue
     */
    protected function setArgument(string $argumentName, string|int|float|bool|array|RawObject $argumentValue): static
    {
        $this->argumentsList[$argumentName] = $argumentValue;

        return $this;
    }

    protected function setVariable(
        string $name,
        string $type,
        bool $isRequired = false,
        string|int|float|bool|null $defaultValue = null
    ): static {
        $this->variables[] = new Variable($name, $type, $isRequired, $defaultValue);

        return $this;
    }
}
