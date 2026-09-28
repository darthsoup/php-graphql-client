<?php

namespace GraphQL;

use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\Util\StringLiteralFormatter;

class Query extends NestableObject implements \Stringable
{
    use FieldTrait;

    protected const string QUERY_FORMAT = '%s%s%s';
    protected const string OPERATION_TYPE = 'query';

    protected string $operationName;
    protected string $fieldName;
    protected string $alias;

    /** @var array<int, Variable> */
    protected array $variables;

    /** @var array<string, mixed> */
    protected array $arguments;

    /** @var array<int, FragmentDefinition> */
    protected array $fragmentDefinitions;

    public function __construct(string $fieldName = '', string $alias = '')
    {
        $this->fieldName = $fieldName;
        $this->alias = $alias;
        $this->operationName = '';
        $this->variables = [];
        $this->arguments = [];
        $this->selectionSet = [];
        $this->fragmentDefinitions = [];
    }

    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function setOperationName(string $operationName): static
    {
        $this->operationName = $operationName === '' ? '' : ' ' . $operationName;

        return $this;
    }

    /** @param array<int, mixed> $fragments */
    public function setFragmentDefinitions(array $fragments): static
    {
        foreach ($fragments as $fragment) {
            if (!$fragment instanceof FragmentDefinition) {
                throw new \InvalidArgumentException('Fragments must be GraphQL\\FragmentDefinition objects');
            }
        }

        $this->fragmentDefinitions = $fragments;

        return $this;
    }

    /**
     * @param array<int, Variable> $variables
     *
     * @throws InvalidVariableException
     */
    public function setVariables(array $variables): static
    {
        /** @var array<int, mixed> $variablesToValidate */
        $variablesToValidate = $variables;
        $nonVarElements = array_filter($variablesToValidate, fn ($element) => !$element instanceof Variable);
        if (count($nonVarElements) > 0) {
            throw new InvalidVariableException(
                'At least one of the elements of the variables array provided is not an instance of GraphQL\\Variable'
            );
        }

        $this->variables = $variables;

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @throws ArgumentException
     */
    public function setArguments(array $arguments): static
    {
        /** @var array<array-key, mixed> $argumentsToValidate */
        $argumentsToValidate = $arguments;
        $nonStringArgs = array_filter(array_keys($argumentsToValidate), fn ($element) => !is_string($element));
        if (!empty($nonStringArgs)) {
            throw new ArgumentException(
                'One or more of the arguments provided for creating the query does not have a key, '
                . 'which represents argument name'
            );
        }

        $this->arguments = $arguments;

        return $this;
    }

    protected function constructVariables(): string
    {
        if (empty($this->variables)) {
            return '';
        }

        $varsString = '(';
        $first = true;
        foreach ($this->variables as $variable) {
            if ($first) {
                $first = false;
            } else {
                $varsString .= ' ';
            }

            $varsString .= (string) $variable;
        }

        return $varsString . ')';
    }

    protected function constructArguments(): string
    {
        if (empty($this->arguments)) {
            return '';
        }

        $constraintsString = '(';
        $first = true;
        foreach ($this->arguments as $name => $value) {
            if ($first) {
                $first = false;
            } else {
                $constraintsString .= ' ';
            }

            $constraintsString .= $name . ': ' . StringLiteralFormatter::formatAnyValue($value);
        }

        return $constraintsString . ')';
    }

    public function __toString(): string
    {
        if ($this->fieldName === '') {
            $query = $this->generateSignature() . $this->constructDirectives() . $this->constructSelectionSet();
        } else {
            $query = $this->generateSignature() . ' {' . PHP_EOL . $this->toFieldString() . PHP_EOL . '}';
        }

        foreach ($this->fragmentDefinitions as $fragment) {
            $query .= PHP_EOL . (string) $fragment;
        }

        return $query;
    }

    public function toFieldString(): string
    {
        return sprintf(
            static::QUERY_FORMAT,
            $this->generateFieldName(),
            $this->constructArguments() . $this->constructDirectives(),
            $this->constructSelectionSet()
        );
    }

    protected function generateFieldName(): string
    {
        return empty($this->alias) ? $this->fieldName : sprintf('%s: %s', $this->alias, $this->fieldName);
    }

    protected function generateSignature(): string
    {
        return sprintf('%s%s%s', static::OPERATION_TYPE, $this->operationName, $this->constructVariables());
    }

    protected function setAsNested(): void
    {
        // Kept for compatibility with NestableObject; rendering no longer mutates the query.
    }
}
