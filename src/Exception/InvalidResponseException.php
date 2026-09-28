<?php

namespace GraphQL\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class InvalidResponseException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ResponseInterface $response,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
