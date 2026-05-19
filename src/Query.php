<?php

namespace GraphQL;

use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\Util\StringLiteralFormatter;

/**
 * Class Query
 *
 * @package GraphQL
 */
class Query extends NestableObject
{
    use FieldTrait;

    protected const QUERY_FORMAT = '%s%s%s';

    protected const OPERATION_TYPE = 'query';

    protected string $operationName;

    protected string $fieldName;

    protected string $alias;

    /** @var array<int, Variable> */
    protected array $variables;

    /** @var array<string, string|int|float|bool|array<mixed>|RawObject|null> */
    protected array $arguments;

    protected bool $isNested;

    public function __construct(string $fieldName = '', string $alias = '')
    {
        $this->fieldName = $fieldName;
        $this->alias = $alias;
        $this->operationName = '';
        $this->variables = [];
        $this->arguments = [];
        $this->selectionSet = [];
        $this->isNested = false;
    }

    /**
     * @return Query
     */
    public function setAlias(string $alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @return Query
     */
    public function setOperationName(string $operationName)
    {
        if (!empty($operationName)) {
            $this->operationName = " $operationName";
        }

        return $this;
    }

    /**
     * @param array<int, Variable> $variables
     *
     * @return Query
     */
    public function setVariables(array $variables)
    {
        /** @var array<int, mixed> $variablesToValidate */
        $variablesToValidate = $variables;
        $nonVarElements = array_filter($variablesToValidate, fn($element) => !$element instanceof Variable);
        if (count($nonVarElements) > 0) {
            throw new InvalidVariableException('At least one of the elements of the variables array provided is not an instance of GraphQL\\Variable');
        }

        $this->variables = $variables;

        return $this;
    }

    /**
     * @param array<string, string|int|float|bool|array<mixed>|RawObject|null> $arguments
     *
     * @throws ArgumentException
     */
    public function setArguments(array $arguments): Query
    {
        /** @var array<array-key, string|int|float|bool|array<mixed>|RawObject|null> $argumentsToValidate */
        $argumentsToValidate = $arguments;
        $nonStringArgs = array_filter(array_keys($argumentsToValidate), fn($element) => !is_string($element));
        if (!empty($nonStringArgs)) {
            throw new ArgumentException(
                'One or more of the arguments provided for creating the query does not have a key, which represents argument name'
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

            if (is_scalar($value) || $value === null) {
                $value = StringLiteralFormatter::formatValueForRHS($value);
            } elseif (is_array($value)) {
                $value = StringLiteralFormatter::formatArrayForGQLQuery($value);
            } else {
                $value = (string) $value;
            }

            $constraintsString .= $name . ': ' . $value;
        }

        return $constraintsString . ')';
    }

    public function __toString(): string
    {
        $queryFormat = static::QUERY_FORMAT;
        $selectionSetString = $this->constructSelectionSet();

        if (!$this->isNested) {
            $queryFormat = $this->generateSignature();
            if ($this->fieldName === '') {
                return $queryFormat . $selectionSetString;
            }

            $queryFormat = $this->generateSignature() . ' {' . PHP_EOL . static::QUERY_FORMAT . PHP_EOL . '}';
        }

        return sprintf($queryFormat, $this->generateFieldName(), $this->constructArguments(), $selectionSetString);
    }

    protected function generateFieldName(): string
    {
        return empty($this->alias) ? $this->fieldName : sprintf('%s: %s', $this->alias, $this->fieldName);
    }

    protected function generateSignature(): string
    {
        return sprintf('%s%s%s', static::OPERATION_TYPE, $this->operationName, $this->constructVariables());
    }

    protected function setAsNested()
    {
        $this->isNested = true;
    }
}
