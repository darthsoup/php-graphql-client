<?php

declare(strict_types=1);

namespace GraphQL;

use Generator;
use GraphQL\Auth\AuthInterface;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\InvalidResponseException;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilderInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use JsonException;

class Client
{
    protected string $endpointUrl;
    protected ClientInterface $httpClient;

    /** @var array<string, string> */
    protected array $httpHeaders;

    /** @var array<string, mixed> */
    protected array $options;

    protected string $requestMethod;
    protected ?AuthInterface $auth = null;

    /**
     * @param array<string, string> $authorizationHeaders
     * @param array<string, mixed> $httpOptions
     */
    public function __construct(
        string $endpointUrl,
        array $authorizationHeaders = [],
        array $httpOptions = [],
        ?ClientInterface $httpClient = null,
        string $requestMethod = 'POST',
        ?AuthInterface $auth = null
    ) {
        $optionHeaders = [];
        if (isset($httpOptions['headers']) && is_array($httpOptions['headers'])) {
            foreach ($httpOptions['headers'] as $header => $value) {
                if (is_string($header) && is_string($value)) {
                    $optionHeaders[$header] = $value;
                }
            }
        }

        $headers = array_merge(
            $authorizationHeaders,
            $optionHeaders,
            ['Content-Type' => 'application/json']
        );

        unset($httpOptions['headers']);
        $this->options = $httpOptions;
        $this->auth = $auth;
        $this->endpointUrl = $endpointUrl;
        $this->httpClient = $httpClient ?? new \GuzzleHttp\Client($httpOptions);
        $this->httpHeaders = $headers;

        if ($requestMethod !== 'POST' && $requestMethod !== 'QUERY') {
            throw new MethodNotSupportedException($requestMethod);
        }

        $this->requestMethod = $requestMethod;
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws QueryError
     * @throws InvalidResponseException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function runQuery(
        AbstractOperation|QueryBuilderInterface $query,
        bool $resultsAsArray = false,
        array $variables = []
    ): Results {
        if ($query instanceof QueryBuilderInterface) {
            $query = $query->getQuery();
        }

        return $this->runRawQuery((string) $query, $resultsAsArray, $variables);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws QueryError
     * @throws InvalidResponseException
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    public function runRawQuery(string $queryString, bool $resultsAsArray = false, array $variables = []): Results
    {
        $request = new Request($this->requestMethod, $this->endpointUrl);

        foreach ($this->httpHeaders as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        $payloadVariables = $variables === [] ? (object) null : $variables;
        $bodyArray = ['query' => $queryString, 'variables' => $payloadVariables];
        $encodedBody = json_encode($bodyArray, JSON_THROW_ON_ERROR);
        $request = $request->withBody(Utils::streamFor($encodedBody));

        if ($this->auth !== null) {
            $request = $this->auth->run($request, $this->options);
        }

        $response = $this->httpClient->sendRequest($request);

        $status = $response->getStatusCode();
        if ($status === 400) {
            new Results($response, $resultsAsArray);

            throw new InvalidResponseException('GraphQL endpoint returned HTTP 400 without GraphQL errors', $response);
        }
        if ($status < 200 || $status >= 300) {
            throw new InvalidResponseException("GraphQL endpoint returned HTTP $status", $response);
        }

        return new Results($response, $resultsAsArray);
    }

    /**
     * Yield records from a paginated GraphQL field, fetching each page on demand.
     *
     * @param array<string, mixed> $variables Non-pagination variables
     * @return Generator<int, array<string, mixed>>
     */
    public function paginate(AbstractOperation|QueryBuilderInterface|string $query, Pagination $pagination, array $variables = []): Generator
    {
        if ($query instanceof QueryBuilderInterface) {
            $query = $query->getQuery();
        }

        return $pagination->iterate($this, (string) $query, $variables);
    }
}
