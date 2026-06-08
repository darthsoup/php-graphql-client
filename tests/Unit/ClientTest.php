<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Client;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    protected Client $client;

    protected MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handler = HandlerStack::create($this->mockHandler);
        $this->client      = new Client('', [], ['handler' => $handler]);
    }

    /**
     * Builds a client whose outgoing requests are captured into $history.
     *
     * @param array<string, string> $authorizationHeaders
     * @param array<string, mixed>  $httpOptions
     * @param array<int, array<string, mixed>> $history captured request/response history (by reference)
     */
    private function clientWithHistory(array $authorizationHeaders, array $httpOptions, array &$history): Client
    {
        $mockHandler = new MockHandler();
        $handler = HandlerStack::create($mockHandler);
        $handler->push(Middleware::history($history));
        $mockHandler->append(new Response(200));

        return new Client('', $authorizationHeaders, array_merge(['handler' => $handler], $httpOptions));
    }

    #[Test]
    public function testSendsQueryAsPostBody(): void
    {
        $history = [];
        $client = $this->clientWithHistory([], [], $history);

        $client->runRawQuery('query_string');

        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('{"query":"query_string","variables":{}}', $request->getBody()->getContents());
    }

    #[Test]
    public function testSendsAuthorizationHeader(): void
    {
        $history = [];
        $client = $this->clientWithHistory(['Authorization' => 'Basic xyz'], [], $history);

        $client->runRawQuery('query_string');

        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertSame(['Basic xyz'], $request->getHeader('Authorization'));
    }

    #[Test]
    public function testSendsQueryVariables(): void
    {
        $history = [];
        $client = $this->clientWithHistory([], [], $history);

        $client->runRawQuery('query_string', false, ['name' => 'val']);

        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertSame(
            '{"query":"query_string","variables":{"name":"val"}}',
            $request->getBody()->getContents()
        );
    }

    #[Test]
    public function testHttpOptionHeadersOverrideAuthorizationHeaders(): void
    {
        $history = [];
        $client = $this->clientWithHistory(
            ['Authorization' => 'Basic xyz'],
            ['headers' => ['Authorization' => 'Basic zyx', 'User-Agent' => 'test']],
            $history
        );

        $client->runRawQuery('query_string');

        /** @var Request $request */
        $request = $history[0]['request'];
        $this->assertSame(['Basic zyx'], $request->getHeader('Authorization'));
        $this->assertSame(['test'], $request->getHeader('User-Agent'));
    }

    #[Test]
    public function testConstructClientWithGetRequestMethod(): void
    {
        $this->expectException(MethodNotSupportedException::class);
        new Client('', [], [], null, 'GET');
    }

    #[Test]
    public function testRunQueryBuilder(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => [
                'someData'
            ]
        ])));

        $response = $this->client->runQuery((new QueryBuilder('obj'))->selectField('field'));
        $this->assertNotNull($response->getData());
    }

    #[Test]
    public function testRunInvalidQueryClass(): void
    {
        $this->expectException(TypeError::class);
        $this->client->runQuery(new RawObject('obj'));
    }

    #[Test]
    public function testValidQueryResponse(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ], [
                        'data' => 'value',
                    ]
                ]
            ]
        ])));

        $objectResults = $this->client->runRawQuery('');
        $this->assertIsObject($objectResults->getResults());
    }

    #[Test]
    public function testValidQueryResponseToArray(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ], [
                        'data' => 'value',
                    ]
                ]
            ]
        ])));

        $arrayResults = $this->client->runRawQuery('', true);
        $this->assertIsArray($arrayResults->getResults());
    }

    #[Test]
    public function testInvalidQueryResponseWith200(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'errors' => [
                [
                    'message' => 'some syntax error',
                    'location' => [
                        [
                            'line' => 1,
                            'column' => 3,
                        ]
                    ],
                ]
            ]
        ])));

        $this->expectException(QueryError::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testInvalidQueryResponseWith400(): void
    {
        $this->mockHandler->append(new ClientException(
            '',
            new Request('post', ''),
            new Response(400, [], json_encode([
                'errors' => [
                    [
                        'message' => 'some syntax error',
                        'location' => [
                            [
                                'line' => 1,
                                'column' => 3,
                            ]
                        ],
                    ]
                ]
            ]))
        ));

        $this->expectException(QueryError::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testUnauthorizedResponse(): void
    {
        $this->mockHandler->append(new ClientException(
            '',
            new Request('post', ''),
            new Response(401, [], json_encode('Unauthorized'))
        ));

        $this->expectException(ClientException::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testNotFoundResponse(): void
    {
        $this->mockHandler->append(new ClientException('', new Request('post', ''), new Response(404, [])));

        $this->expectException(ClientException::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testInternalServerErrorResponse(): void
    {
        $this->mockHandler->append(new ServerException('', new Request('post', ''), new Response(500, [])));

        $this->expectException(ServerException::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testConnectTimeoutResponse(): void
    {
        $this->mockHandler->append(new ConnectException('Time Out', new Request('post', '')));
        $this->expectException(ConnectException::class);
        $this->client->runRawQuery('');
    }
}
