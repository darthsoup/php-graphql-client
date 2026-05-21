<?php

namespace GraphQL;

use GraphQL\Auth\AuthInterface;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilderInterface;
use GraphQL\Util\GuzzleAdapter;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;

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
        $this->httpClient = $httpClient ?? new GuzzleAdapter(new \GuzzleHttp\Client($httpOptions));
        $this->httpHeaders = $headers;

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
        $request = new Request($this->requestMethod, $this->endpointUrl);

        foreach ($this->httpHeaders as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        $payloadVariables = $variables === [] ? (object) null : $variables;
        $bodyArray = ['query' => $queryString, 'variables' => $payloadVariables];
        $encodedBody = json_encode($bodyArray);
        if ($encodedBody === false) {
            $encodedBody = '';
        }
        $request = $request->withBody(Utils::streamFor($encodedBody));

        if ($this->auth !== null) {
            $request = $this->auth->run($request, $this->options);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
            if ($response->getStatusCode() !== 400) {
                throw $exception;
            }
        }

        return new Results($response, $resultsAsArray);
    }
}
