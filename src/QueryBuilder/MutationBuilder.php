<?php

declare(strict_types=1);

namespace GraphQL\QueryBuilder;

use GraphQL\Mutation;

class MutationBuilder extends QueryBuilder
{
    #[\Override]
    protected function createOperation(string $queryObject, string $alias): Mutation
    {
        return new Mutation($queryObject, $alias);
    }

    public function getMutation(): Mutation
    {
        $mutation = $this->getQuery();
        assert($mutation instanceof Mutation);

        return $mutation;
    }
}
