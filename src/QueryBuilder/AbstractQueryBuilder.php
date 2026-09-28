<?php

namespace GraphQL\QueryBuilder;

use GraphQL\InlineFragment;
use GraphQL\Query;
use GraphQL\RawObject;
use GraphQL\InputObject;
use GraphQL\VariableReference;
use GraphQL\Directive;
use GraphQL\FragmentDefinition;
use GraphQL\FragmentSpread;
use GraphQL\Variable;

abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    protected Query $query;

    /** @var array<int, Variable> */
    private array $variables;

    /** @var array<int, string|QueryBuilderInterface|Query|InlineFragment|FragmentSpread> */
    private array $selectionSet;

    /** @var array<int, Directive> */
    private array $directives = [];

    /** @var array<int, FragmentDefinition> */
    private array $fragmentDefinitions = [];

    /** @var array<string, string|int|float|bool|array<mixed>|RawObject|InputObject|VariableReference|null> */
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
        $this->query->setDirectives($this->directives);
        $this->query->setFragmentDefinitions($this->fragmentDefinitions);

        return $this->query;
    }

    protected function selectField(
        string|QueryBuilderInterface|Query|InlineFragment|FragmentSpread $selectedField
    ): static {
        $this->selectionSet[] = $selectedField;

        return $this;
    }

    /** @param array<int, Directive> $directives */
    public function setDirectives(array $directives): static
    {
        $this->directives = $directives;

        return $this;
    }

    /** @param array<int, FragmentDefinition> $fragments */
    public function setFragmentDefinitions(array $fragments): static
    {
        $this->fragmentDefinitions = $fragments;

        return $this;
    }

    /**
     * @param array<mixed>|string|int|float|bool|RawObject|InputObject|VariableReference|null $argumentValue
     */
    protected function setArgument(
        string $argumentName,
        string|int|float|bool|array|RawObject|InputObject|VariableReference|null $argumentValue
    ): static {
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
