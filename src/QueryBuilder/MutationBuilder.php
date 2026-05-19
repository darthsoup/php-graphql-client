<?php

namespace GraphQL\QueryBuilder;

use GraphQL\Mutation;

class MutationBuilder extends QueryBuilder
{
    public function __construct(string $queryObject = '', string $alias = '')
    {
        parent::__construct($queryObject, $alias);
        $this->query = new Mutation($queryObject, $alias);
    }

    public function getMutation(): Mutation
    {
        $mutation = $this->getQuery();
        assert($mutation instanceof Mutation);

        return $mutation;
    }
}
