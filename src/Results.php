<?php

namespace GraphQL;

use GraphQL\Exception\QueryError;
use Psr\Http\Message\ResponseInterface;

class Results
{
    protected string $responseBody;
    protected ResponseInterface $responseObject;

    /** @var array<string, mixed>|object */
    protected array|object $results;

    /** @throws QueryError */
    public function __construct(ResponseInterface $response, bool $asArray = false)
    {
        $this->responseObject = $response;
        $this->responseBody = $this->responseObject->getBody()->getContents();
        $this->results = $this->decodeResponse($asArray);

        if ($asArray) {
            /** @var array<string, mixed> $results */
            $results = $this->results;
            $containsErrors = array_key_exists('errors', $results);
        } else {
            /** @var object{errors?: mixed} $results */
            $results = $this->results;
            $containsErrors = isset($results->errors);
        }

        if ($containsErrors) {
            $this->reformatResults(true);
            assert(is_array($this->results));
            throw new QueryError($this->results);
        }
    }

    public function reformatResults(bool $asArray): void
    {
        $this->results = $this->decodeResponse($asArray);
    }

    /** @return array<string, mixed>|object */
    public function getData(): array|object
    {
        if (is_array($this->results)) {
            /** @var array<string, mixed>|object $data */
            $data = $this->results['data'];

            return $data;
        }

        /** @var object{data: array<string, mixed>|object} $results */
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
        $results = json_decode($this->responseBody, $asArray);
        if (is_array($results) || is_object($results)) {
            return $results;
        }

        return $asArray ? [] : (object) [];
    }
}
