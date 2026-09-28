<?php

namespace GraphQL\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/** Thrown when the GraphQL endpoint returns an error for the provided query. */
class QueryError extends RuntimeException
{
    /** @var array<string, mixed> */
    protected array $errorDetails;

    /** @var array<int, array<string, mixed>> */
    private array $errors;

    /** @var array<string, mixed> */
    private array $responseData;

    /**
     * @param array<string, mixed> $errorDetails
     */
    public function __construct(
        array $errorDetails,
        private readonly ?ResponseInterface $responseObject = null
    ) {
        $this->responseData = $errorDetails;
        $errors = $errorDetails['errors'] ?? [];
        $this->errors = [];
        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (is_array($error)) {
                    $this->errors[] = $error;
                }
            }
        }

        $this->errorDetails = $this->errors[0] ?? [];

        $message = $this->errorDetails['message'] ?? '';
        parent::__construct(is_string($message) ? $message : '');
    }

    /** @return array<string, mixed> */
    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    /** @return array<int, array<string, mixed>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed>|null */
    public function getData(): ?array
    {
        $data = $this->responseData['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /** @return array<string, mixed> */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    public function getResponseObject(): ?ResponseInterface
    {
        return $this->responseObject;
    }
}
