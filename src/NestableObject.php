<?php

declare(strict_types=1);

namespace GraphQL;

abstract class NestableObject
{
    /** @codeCoverageIgnore */
    abstract protected function setAsNested(): void;
}
