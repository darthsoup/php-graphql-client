<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Client;
use GraphQL\Exception\MethodNotSupportedException;
use GraphQL\Exception\QueryError;
use GraphQL\Pagination;
use GraphQL\Query;
use JsonException;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;
use InvalidArgumentException;
use UnexpectedValueException;

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
        $mockHandler->append(new Response(200, [], '{"data":{}}'));

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
    public function testRejectsVariablesThatCannotBeEncoded(): void
    {
        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);

        try {
            $this->expectException(JsonException::class);
            $this->client->runRawQuery('query { field }', false, ['bad' => $resource]);
        } finally {
            fclose($resource);
        }
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
                'someData' => 'value'
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

    /** @return iterable<string, array{bool}> */
    public static function lighthousePageModes(): iterable
    {
        yield 'PAGINATOR' => [true];
        yield 'SIMPLE' => [false];
    }

    #[Test]
    #[DataProvider('lighthousePageModes')]
    public function testLighthousePagesAreLazyAndPreserveVariables(bool $withTotal): void
    {
        $history = [];
        $firstInfo = ['hasMorePages' => true];
        if ($withTotal) {
            $firstInfo['total'] = 3;
        }
        $client = $this->paginationClient([
            ['data' => ['viewer' => ['posts' => [
                'data' => [['id' => '1'], ['id' => '2']],
                'paginatorInfo' => $firstInfo,
            ]]]],
            ['data' => ['viewer' => ['posts' => [
                'data' => [['id' => '3']],
                'paginatorInfo' => ['hasMorePages' => false],
            ]]]],
        ], $history);

        $query = (new Query('viewer'))->setSelectionSet(['posts']);
        $records = $client->paginate($query, Pagination::lighthousePages('viewer.posts', 2), ['status' => 'active']);
        self::assertCount(0, $history);
        self::assertSame([['id' => '1'], ['id' => '2'], ['id' => '3']], iterator_to_array($records));
        self::assertSame([
            ['status' => 'active', 'first' => 2, 'page' => 1],
            ['status' => 'active', 'first' => 2, 'page' => 2],
        ], $this->sentVariables($history));
    }

    #[Test]
    public function testConnectionUsesEndCursorAndUnwrapsNodes(): void
    {
        $history = [];
        $client = $this->paginationClient([
            ['data' => ['posts' => [
                'edges' => [['node' => ['id' => '1']], ['node' => ['id' => '2']]],
                'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor-2'],
            ]]],
            ['data' => ['posts' => [
                'edges' => [['node' => ['id' => '3']]],
                'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
            ]]],
        ], $history);

        $builder = (new QueryBuilder('posts'))->selectField('edges');
        $records = iterator_to_array($client->paginate(
            $builder,
            Pagination::lighthouseConnection('posts', 2),
            ['filter' => 'published']
        ));

        self::assertSame([['id' => '1'], ['id' => '2'], ['id' => '3']], $records);
        self::assertSame([
            ['filter' => 'published', 'first' => 2, 'after' => null],
            ['filter' => 'published', 'first' => 2, 'after' => 'cursor-2'],
        ], $this->sentVariables($history));
    }

    #[Test]
    public function testOffsetPaginationStopsAtTotalAndEarlyBreakDoesNotFetchNextPage(): void
    {
        $history = [];
        $client = $this->paginationClient([
            ['data' => ['pokemons' => ['count' => 4, 'results' => [['name' => 'one'], ['name' => 'two']]]]],
            ['data' => ['pokemons' => ['count' => 4, 'results' => [['name' => 'three'], ['name' => 'four']]]]],
        ], $history);

        $records = [];
        foreach ($client->paginate('query { pokemons { results { name } count } }', Pagination::offset('pokemons', 2)) as $record) {
            $records[] = $record;
            if (count($records) === 2) {
                break;
            }
        }
        self::assertCount(1, $history);
        self::assertSame([['name' => 'one'], ['name' => 'two']], $records);

        // A new iterator can still consume both pages from a fresh client.
        $completeHistory = [];
        $completeClient = $this->paginationClient([
            ['data' => ['pokemons' => ['count' => 4, 'results' => [['name' => 'one'], ['name' => 'two']]]]],
            ['data' => ['pokemons' => ['count' => 4, 'results' => [['name' => 'three'], ['name' => 'four']]]]],
        ], $completeHistory);
        self::assertCount(4, iterator_to_array($completeClient->paginate('query { pokemons }', Pagination::offset('pokemons', 3))));
        self::assertSame(
            [['limit' => 3, 'offset' => 0], ['limit' => 3, 'offset' => 2]],
            $this->sentVariables($completeHistory)
        );
    }

    #[Test]
    public function testEmptyFinalPageAndMalformedMetadata(): void
    {
        $history = [];
        $client = $this->paginationClient([
            ['data' => ['posts' => ['data' => [], 'paginatorInfo' => ['hasMorePages' => false]]]],
        ], $history);
        self::assertSame([], iterator_to_array($client->paginate('query { posts }', Pagination::lighthousePages('posts', 5))));

        $brokenClient = $this->paginationClient([
            ['data' => ['posts' => ['data' => [], 'paginatorInfo' => ['hasMorePages' => true]]]],
        ], $history);
        $this->expectException(UnexpectedValueException::class);
        iterator_to_array($brokenClient->paginate('query { posts }', Pagination::lighthousePages('posts', 5)));
    }

    #[Test]
    public function testConnectionRejectsRepeatedCursor(): void
    {
        $history = [];
        $client = $this->paginationClient([
            ['data' => ['posts' => [
                'edges' => [['node' => ['id' => '1']]],
                'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'same'],
            ]]],
        ], $history);

        $this->expectException(UnexpectedValueException::class);
        iterator_to_array($client->paginate('query { posts }', Pagination::lighthouseConnection('posts', 2, 'same')));
    }

    #[Test]
    public function testPaginationRejectsMissingMetadata(): void
    {
        $history = [];
        $client = $this->paginationClient([
            ['data' => ['posts' => ['data' => [['id' => '1']], 'paginatorInfo' => []]]],
        ], $history);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('hasMorePages');
        iterator_to_array($client->paginate('query { posts }', Pagination::lighthousePages('posts', 2)));
    }

    #[Test]
    public function testPaginationRejectsConflictingVariables(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array($this->client->paginate('query { posts }', Pagination::lighthousePages('posts', 2), ['page' => 5]));
    }

    #[Test]
    public function testPaginationPreservesQueryAndTransportExceptions(): void
    {
        $history = [];
        $queryClient = $this->paginationClient([['errors' => [['message' => 'bad query']]]], $history);
        try {
            iterator_to_array($queryClient->paginate('query { posts }', Pagination::lighthousePages('posts', 2)));
            self::fail('Expected a query error');
        } catch (QueryError $exception) {
            self::assertSame('bad query', $exception->getMessage());
        }

        $this->mockHandler->append(new ConnectException('Time Out', new Request('post', '')));
        $this->expectException(ConnectException::class);
        iterator_to_array($this->client->paginate('query { posts }', Pagination::lighthousePages('posts', 2)));
    }

    /**
     * @param list<array<string, mixed>> $pages
     * @param array<int, array<string, mixed>> $history
     */
    private function paginationClient(array $pages, array &$history): Client
    {
        $responses = array_map(
            static fn (array $page): Response => new Response(200, [], json_encode($page, JSON_THROW_ON_ERROR)),
            $pages
        );
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));

        return new Client('https://example.test/graphql', [], ['handler' => $handler]);
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @return list<array<string, mixed>>
     */
    private function sentVariables(array $history): array
    {
        return array_map(static function (array $entry): array {
            /** @var Request $request */
            $request = $entry['request'];
            $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $body['variables'];
        }, $history);
    }
}
