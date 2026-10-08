<?php

declare(strict_types=1);

namespace GraphQL\QueryBuilder;

use GraphQL\AbstractOperation;

interface QueryBuilderInterface
{
    public function getQuery(): AbstractOperation;
}
