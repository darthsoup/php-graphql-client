<?php

namespace GraphQL\Auth;

use Psr\Http\Message\RequestInterface;

interface AuthInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function run(RequestInterface $request, array $options = []): RequestInterface;
}
