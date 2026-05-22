<?php

namespace GraphQL\Tests;

use GraphQL\Client;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilder;
use GraphQL\RawObject;
use GraphQL\Results;
use GraphQL\Tests\Util\GuzzleAdapter;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;
use TypeError;

class ClientTest extends TestCase
{
    protected Client $client;

    protected MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handler = HandlerStack::create($this->mockHandler);
        $guzzle = new \GuzzleHttp\Client(['handler' => $handler]);
        $this->client = new Client('', [], [], new GuzzleAdapter($guzzle));
    }

    #[Test]
    public function testConstructClient(): void
    {
        $mockHandler = new MockHandler();
        $handler = HandlerStack::create($mockHandler);
        $container = [];
        $history = Middleware::history($container);
        $handler->push($history);

        $mockHandler->append(new Response(200, [], json_encode(['data' => null])));
        $mockHandler->append(new Response(200, [], json_encode(['data' => null])));
        $mockHandler->append(new Response(200, [], json_encode(['data' => null])));
        $mockHandler->append(new Response(200, [], json_encode(['data' => null])));

        $guzzle = new \GuzzleHttp\Client(['handler' => $handler]);
        $psrClient = new GuzzleAdapter($guzzle);

        $client = new Client('', [], [], $psrClient);
        $client->runRawQuery('query_string');

        $client = new Client('', ['Authorization' => 'Basic xyz'], [], $psrClient);
        $client->runRawQuery('query_string');

        $client = new Client('', [], [], $psrClient);
        $client->runRawQuery('query_string', false, ['name' => 'val']);

        $client = new Client('', ['Authorization' => 'Basic xyz'], [
            'headers' => ['Authorization' => 'Basic zyx', 'User-Agent' => 'test'],
        ], $psrClient);
        $client->runRawQuery('query_string');

        /** @var Request $firstRequest */
        $firstRequest = $container[0]['request'];
        $this->assertEquals('{"query":"query_string","variables":{}}', $firstRequest->getBody()->getContents());
        $this->assertSame('POST', $firstRequest->getMethod());

        /** @var Request $secondRequest */
        $secondRequest = $container[1]['request'];
        $this->assertNotEmpty($secondRequest->getHeader('Authorization'));
        $this->assertEquals(['Basic xyz'], $secondRequest->getHeader('Authorization'));

        /** @var Request $thirdRequest */
        $thirdRequest = $container[2]['request'];
        $this->assertEquals(
            '{"query":"query_string","variables":{"name":"val"}}',
            $thirdRequest->getBody()->getContents(),
        );

        /** @var Request $fourthRequest */
        $fourthRequest = $container[3]['request'];
        $this->assertNotEmpty($fourthRequest->getHeader('Authorization'));
        $this->assertNotEmpty($fourthRequest->getHeader('User-Agent'));
        $this->assertEquals(['Basic zyx'], $fourthRequest->getHeader('Authorization'));
        $this->assertEquals(['test'], $fourthRequest->getHeader('User-Agent'));
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
        $this->mockHandler->append(new Response(200, [], json_encode(['data' => ['someData']])));

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
            'data' => ['someField' => [['data' => 'value'], ['data' => 'value']]],
        ])));

        $objectResults = $this->client->runRawQuery('');
        $this->assertIsObject($objectResults->getResults());
    }

    #[Test]
    public function testValidQueryResponseToArray(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'data' => ['someField' => [['data' => 'value'], ['data' => 'value']]],
        ])));

        $arrayResults = $this->client->runRawQuery('', true);
        $this->assertIsArray($arrayResults->getResults());
    }

    #[Test]
    public function testInvalidQueryResponseWith200(): void
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'errors' => [['message' => 'some syntax error', 'location' => [['line' => 1, 'column' => 3]]]],
        ])));

        $this->expectException(QueryError::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testInvalidQueryResponseWith400(): void
    {
        $this->mockHandler->append(new Response(400, [], json_encode([
            'errors' => [['message' => 'some syntax error', 'location' => [['line' => 1, 'column' => 3]]]],
        ])));

        $this->expectException(QueryError::class);
        $this->client->runRawQuery('');
    }

    #[Test]
    public function testUnauthorizedResponse(): void
    {
        $this->mockHandler->append(new Response(401, [], json_encode(['data' => null])));

        $result = $this->client->runRawQuery('');
        $this->assertInstanceOf(Results::class, $result);
    }

    #[Test]
    public function testNotFoundResponse(): void
    {
        $this->mockHandler->append(new Response(404, [], json_encode(['data' => null])));

        $result = $this->client->runRawQuery('');
        $this->assertInstanceOf(Results::class, $result);
    }

    #[Test]
    public function testInternalServerErrorResponse(): void
    {
        $this->mockHandler->append(new Response(500, [], json_encode(['data' => null])));

        $result = $this->client->runRawQuery('');
        $this->assertInstanceOf(Results::class, $result);
    }

    #[Test]
    public function testConnectTimeoutResponse(): void
    {
        $this->mockHandler->append(new ConnectException('Time Out', new Request('POST', '')));

        $this->expectException(NetworkExceptionInterface::class);
        $this->client->runRawQuery('');
    }
}
