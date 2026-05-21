<?php

namespace GraphQL;

abstract class NestableObject
{
    /** @codeCoverageIgnore */
    abstract protected function setAsNested(): void;
}
