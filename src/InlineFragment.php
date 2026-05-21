<?php

namespace GraphQL;

use GraphQL\QueryBuilder\QueryBuilderInterface;

class InlineFragment extends NestableObject implements \Stringable
{
    use FieldTrait;

    protected const string FORMAT = '... on %s%s';

    public function __construct(
        protected readonly string $typeName,
        protected readonly ?QueryBuilderInterface $queryBuilder = null
    ) {
    }

    public function __toString(): string
    {
        if ($this->queryBuilder !== null) {
            $this->setSelectionSet($this->queryBuilder->getQuery()->getSelectionSet());
        }

        return sprintf(static::FORMAT, $this->typeName, $this->constructSelectionSet());
    }

    /** @codeCoverageIgnore */
    protected function setAsNested(): void
    {
        // TODO: Remove this method, it's purely tech debt
    }
}
