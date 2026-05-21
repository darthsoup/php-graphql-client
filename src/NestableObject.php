<?php

namespace GraphQL;

abstract class NestableObject
{
    /** @codeCoverageIgnore */
    protected abstract function setAsNested(): void;
}
