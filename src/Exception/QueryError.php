<?php

namespace GraphQL\Exception;

use RuntimeException;

/**
 * This exception is triggered when the GraphQL endpoint returns an error in the provided query
 *
 * Class QueryError
 *
 * @package GraphQl\Exception
 */
class QueryError extends RuntimeException
{
    /** @var array<string, mixed> */
    protected array $errorDetails;

    /**
     * QueryError constructor.
     *
     * @param array<string, mixed> $errorDetails
     */
    public function __construct(array $errorDetails)
    {
        $errors = $errorDetails['errors'] ?? [];
        assert(is_array($errors));

        $firstError = $errors[0] ?? [];
        assert(is_array($firstError));

        /** @var array<string, mixed> $firstError */
        $this->errorDetails = $firstError;

        $message = $this->errorDetails['message'] ?? '';
        parent::__construct(is_string($message) ? $message : '');
    }

    /**
     * @return array<string, mixed>
     */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }
}
