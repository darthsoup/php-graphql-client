<?php

declare(strict_types=1);

namespace GraphQL;

use GraphQL\Exception\InvalidResponseException;
use GraphQL\Exception\QueryError;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class Results
{
    protected string $responseBody;
    protected ResponseInterface $responseObject;

    /** @var array<string, mixed>|object */
    protected array|object $results;

    /** @throws QueryError|InvalidResponseException */
    public function __construct(ResponseInterface $response, bool $asArray = false)
    {
        $this->responseObject = $response;
        $this->responseBody = (string) $this->responseObject->getBody();
        $this->results = $this->decodeResponse($asArray);

        if ($asArray) {
            /** @var array<string, mixed> $results */
            $results = $this->results;
            $containsErrors = !empty($results['errors']);
        } else {
            /** @var object{errors?: mixed} $results */
            $results = $this->results;
            $containsErrors = !empty($results->errors);
        }

        if ($containsErrors) {
            if (!$asArray) {
                $this->reformatResults(true);
            }
            assert(is_array($this->results));
            throw new QueryError($this->results, $this->responseObject);
        }
    }

    public function reformatResults(bool $asArray): void
    {
        $this->results = $this->decodeResponse($asArray);
    }

    /** @return array<string, mixed>|object|null */
    public function getData(): array|object|null
    {
        if (is_array($this->results)) {
            /** @var array<string, mixed>|object|null $data */
            $data = $this->results['data'];

            return $data;
        }

        /** @var object{data: array<string, mixed>|object|null} $results */
        $results = $this->results;

        return $results->data;
    }

    /** @return array<string, mixed>|object */
    public function getResults(): array|object
    {
        return $this->results;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseObject(): ResponseInterface
    {
        return $this->responseObject;
    }

    /** @return array<string, mixed>|object */
    protected function decodeResponse(bool $asArray): array|object
    {
        try {
            $decoded = json_decode($this->responseBody, $asArray, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidResponseException('GraphQL response is not valid JSON', $this->responseObject, $exception);
        }

        if ($asArray) {
            if (!is_array($decoded)) {
                throw new InvalidResponseException('GraphQL response must be an object', $this->responseObject);
            }

            $hasData = array_key_exists('data', $decoded);
            $hasErrors = array_key_exists('errors', $decoded);
            if (!$hasData && !$hasErrors) {
                throw new InvalidResponseException('GraphQL response has no data or errors', $this->responseObject);
            }
            if ($hasData && $decoded['data'] !== null && !is_array($decoded['data'])) {
                throw new InvalidResponseException('GraphQL response data must be an object or null', $this->responseObject);
            }

            // Associative decoding merges JSON objects and arrays. Inspect the object shape
            // only where that distinction matters; normal data responses need one decode.
            $needsShapeCheck = $hasErrors;
            if ($hasData) {
                $data = $decoded['data'];
                if (is_array($data) && array_is_list($data)) {
                    $needsShapeCheck = true;
                }
            }
            if ($needsShapeCheck) {
                $this->validateObjectResponse(json_decode($this->responseBody, false, 512, JSON_THROW_ON_ERROR));
            }

            return $decoded;
        }

        if (!is_object($decoded)) {
            throw new InvalidResponseException('GraphQL response must be an object', $this->responseObject);
        }
        $this->validateObjectResponse($decoded);

        return $decoded;
    }

    private function validateObjectResponse(mixed $decoded): void
    {
        if (!is_object($decoded)) {
            throw new InvalidResponseException('GraphQL response must be an object', $this->responseObject);
        }

        $hasData = property_exists($decoded, 'data');
        $hasErrors = property_exists($decoded, 'errors');
        if (!$hasData && !$hasErrors) {
            throw new InvalidResponseException('GraphQL response has no data or errors', $this->responseObject);
        }

        if ($hasData && $decoded->data !== null && !is_object($decoded->data)) {
            throw new InvalidResponseException(
                'GraphQL response data must be an object or null',
                $this->responseObject
            );
        }

        if ($hasErrors) {
            if (!is_array($decoded->errors)) {
                throw new InvalidResponseException('GraphQL response errors must be a list', $this->responseObject);
            }
            foreach ($decoded->errors as $error) {
                if (!is_object($error)) {
                    throw new InvalidResponseException(
                        'GraphQL response errors must contain objects',
                        $this->responseObject
                    );
                }
            }
        }

        if (!$hasData && $decoded->errors === []) {
            throw new InvalidResponseException('GraphQL response has no data or errors', $this->responseObject);
        }
    }
}
