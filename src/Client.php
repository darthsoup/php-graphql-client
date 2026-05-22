<?php

namespace GraphQL;

use GraphQL\Auth\AuthInterface;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilderInterface;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Client
{
    protected string $endpointUrl;
    protected ClientInterface $httpClient;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;

    /** @var array<string, string> */
    protected array $httpHeaders;

    /** @var array<string, mixed> */
    protected array $options;

    protected string $requestMethod;
    protected ?AuthInterface $auth = null;

    /**
     * @param array<string, string> $authorizationHeaders
     * @param array<string, mixed> $httpOptions Only the 'headers' key is processed for request headers; other options are retained for auth integrations.
     */
    public function __construct(
        string $endpointUrl,
        array $authorizationHeaders = [],
        array $httpOptions = [],
        ?ClientInterface $httpClient = null,
        string $requestMethod = 'POST',
        ?AuthInterface $auth = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $optionHeaders = [];
        if (isset($httpOptions['headers']) && is_array($httpOptions['headers'])) {
            foreach ($httpOptions['headers'] as $header => $value) {
                if (is_string($header) && is_string($value)) {
                    $optionHeaders[$header] = $value;
                }
            }
        }

        $this->httpHeaders = array_merge(
            $authorizationHeaders,
            $optionHeaders,
            ['Content-Type' => 'application/json'],
        );

        unset($httpOptions['headers']);

        $this->options = $httpOptions;
        $this->endpointUrl = $endpointUrl;
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->auth = $auth;

        if ($requestMethod !== 'POST') {
            throw new MethodNotSupportedException($requestMethod);
        }

        $this->requestMethod = $requestMethod;
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @throws QueryError
     */
    public function runQuery(
        Query|QueryBuilderInterface $query,
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
     */
    public function runRawQuery(string $queryString, bool $resultsAsArray = false, array $variables = []): Results
    {
        $request = $this->requestFactory->createRequest($this->requestMethod, $this->endpointUrl);

        foreach ($this->httpHeaders as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        $payloadVariables = $variables === [] ? (object) null : $variables;
        $bodyArray = ['query' => $queryString, 'variables' => $payloadVariables];
        $encodedBody = json_encode($bodyArray) ?: '';
        $request = $request->withBody($this->streamFactory->createStream($encodedBody));

        if ($this->auth !== null) {
            $request = $this->auth->run($request, $this->options);
        }

        $response = $this->httpClient->sendRequest($request);

        return new Results($response, $resultsAsArray);
    }
}
