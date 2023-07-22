<?php

namespace GraphQL\QueryBuilder;

use GraphQL\Query;

/**
 * Interface QueryBuilderInterface
 *
 * @package GraphQL\QueryBuilder
 */
interface QueryBuilderInterface
{
    function getQuery(): Query;
}