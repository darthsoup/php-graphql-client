<?php

namespace GraphQL\Auth;

use GuzzleHttp\Psr7\Request;

interface AuthInterface
{
    public function run(Request $request, array $options = []): Request;
}
