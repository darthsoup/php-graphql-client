<?php

declare(strict_types=1);

namespace GraphQL;

use GraphQL\Exception\ArgumentException;
use GraphQL\Exception\InvalidVariableException;
use GraphQL\Util\StringLiteralFormatter;

abstract class AbstractOperation extends NestableObject implements \Stringable
{
    use FieldTrait;

    protected const string QUERY_FORMAT = '%s%s%s';

    protected string $operationName = '';

    /** @var array<int, Variable> */
    protected array $variables = [];

    /** @var array<string, mixed> */
    protected array $arguments = [];

    /** @var array<int, FragmentDefinition> */
    protected array $fragmentDefinitions = [];

    public function __construct(
        protected string $fieldName = '',
        protected string $alias = ''
    ) {
        self::assertName($fieldName, 'field', true);
        self::assertName($alias, 'alias', true);
        $this->selectionSet = [];
    }

    public function setAlias(string $alias): static
    {
        self::assertName($alias, 'alias', true);
        $this->alias = $alias;

        return $this;
    }

    public function setOperationName(string $operationName): static
    {
        self::assertName($operationName, 'operation', true);
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
        foreach ($variablesToValidate as $variable) {
            if (!$variable instanceof Variable) {
                throw new InvalidVariableException(
                    'At least one of the elements of the variables array provided is not an instance of GraphQL\\Variable'
                );
            }
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
        foreach ($argumentsToValidate as $name => $value) {
            if (!is_string($name)) {
                throw new ArgumentException(
                    'One or more of the arguments provided for creating the query does not have a key, '
                    . 'which represents argument name'
                );
            }
            self::assertName($name, 'argument');
        }

        $this->arguments = $arguments;

        return $this;
    }

    protected function constructVariables(): string
    {
        if ($this->variables === []) {
            return '';
        }

        return '(' . implode(' ', array_map(
            static fn (Variable $variable): string => (string) $variable,
            $this->variables
        )) . ')';
    }

    protected function constructArguments(): string
    {
        if ($this->arguments === []) {
            return '';
        }

        $arguments = [];
        foreach ($this->arguments as $name => $value) {
            $arguments[] = $name . ': ' . StringLiteralFormatter::formatAnyValue($value);
        }

        return '(' . implode(' ', $arguments) . ')';
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
        return $this->alias === '' ? $this->fieldName : sprintf('%s: %s', $this->alias, $this->fieldName);
    }

    protected function generateSignature(): string
    {
        return $this->getOperationType() . $this->operationName . $this->constructVariables();
    }

    abstract protected function getOperationType(): string;

    #[\Override]
    protected function setAsNested(): void
    {
        // Kept for compatibility with NestableObject; rendering no longer mutates the query.
    }

    private static function assertName(string $name, string $kind, bool $allowEmpty = false): void
    {
        if (($name === '' && $allowEmpty) || preg_match('/^[_A-Za-z][_0-9A-Za-z]*$/D', $name)) {
            return;
        }

        throw new \InvalidArgumentException("Invalid GraphQL $kind name");
    }
}
