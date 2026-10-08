<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Client;
use GraphQL\Pagination;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use UnexpectedValueException;

#[CoversClass(Pagination::class)]
final class PaginationTest extends TestCase
{
    public static function invalidConfigurations(): iterable
    {
        foreach (['', '.posts', 'posts.', 'viewer..posts', 'bad-name', '1posts'] as $path) {
            yield 'pages path ' . $path => ['lighthousePages', [$path, 2]];
            yield 'connection path ' . $path => ['lighthouseConnection', [$path, 2]];
            yield 'offset path ' . $path => ['offset', [$path, 2]];
        }
        foreach ([0, -1] as $size) {
            foreach (['lighthousePages', 'lighthouseConnection', 'offset'] as $mode) {
                yield "$mode size $size" => [$mode, ['posts', $size]];
            }
        }
        yield 'zero starting page' => ['lighthousePages', ['posts', 2, 0]];
        yield 'negative starting page' => ['lighthousePages', ['posts', 2, -1]];
        yield 'negative offset' => ['offset', ['posts', 2, -1]];
    }

    #[Test]
    #[DataProvider('invalidConfigurations')]
    public function rejectsInvalidConfiguration(string $factory, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        Pagination::$factory(...$arguments);
    }

    public static function variableConflicts(): iterable
    {
        foreach (['first', 'page'] as $name) {
            yield 'pages ' . $name => [Pagination::lighthousePages('posts', 2), $name];
        }
        foreach (['first', 'after'] as $name) {
            yield 'connection ' . $name => [Pagination::lighthouseConnection('posts', 2), $name];
        }
        foreach (['limit', 'offset'] as $name) {
            yield 'offset ' . $name => [Pagination::offset('posts', 2), $name];
        }
    }

    #[Test]
    #[DataProvider('variableConflicts')]
    public function rejectsControlledVariablesEvenWhenNull(Pagination $pagination, string $name): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $transport->expects(self::never())->method('sendRequest');
        $client = new Client('https://example.test/graphql', [], [], $transport);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Pagination controls the '$name' variable");
        iterator_to_array($client->paginate('query { posts }', $pagination, [$name => null]));
    }

    public static function customStarts(): iterable
    {
        yield 'pages' => [Pagination::lighthousePages('posts', 2, 4), [
            ['data' => [['id' => 1]], 'paginatorInfo' => ['hasMorePages' => true]],
            ['data' => [['id' => 2]], 'paginatorInfo' => ['hasMorePages' => false]],
        ], [['first' => 2, 'page' => 4], ['first' => 2, 'page' => 5]]];
        yield 'offset' => [Pagination::offset('posts', 2, 4), [
            ['results' => [['id' => 1]], 'count' => 6],
            ['results' => [['id' => 2]], 'count' => 6],
        ], [['limit' => 2, 'offset' => 4], ['limit' => 2, 'offset' => 5]]];
        yield 'connection' => [Pagination::lighthouseConnection('posts', 2, 'initial'), [
            ['edges' => [['node' => ['id' => 1]]], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next']],
            ['edges' => [['node' => ['id' => 2]]], 'pageInfo' => ['hasNextPage' => false]],
        ], [['first' => 2, 'after' => 'initial'], ['first' => 2, 'after' => 'next']]];
    }

    #[Test]
    #[DataProvider('customStarts')]
    public function startsAtRequestedPositionAndAdvances(Pagination $pagination, array $pages, array $expectedVariables): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $index = 0;
        $transport->expects(self::exactly(count($pages)))->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $request) use (&$index, $pages, $expectedVariables): Response {
                $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame('query { posts }', $payload['query']);
                self::assertSame(['status' => 'active'] + $expectedVariables[$index], $payload['variables']);

                return new Response(200, [], json_encode(['data' => ['posts' => $pages[$index++]]], JSON_THROW_ON_ERROR));
            });
        $client = new Client('https://example.test/graphql', [], [], $transport);

        self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($client->paginate(
            'query { posts }',
            $pagination,
            ['status' => 'active']
        )));
    }

    public static function emptyFinalPages(): iterable
    {
        yield 'pages' => [Pagination::lighthousePages('posts', 2), [
            'data' => [], 'paginatorInfo' => ['hasMorePages' => false],
        ]];
        yield 'connection' => [Pagination::lighthouseConnection('posts', 2), [
            'edges' => [], 'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
        ]];
        yield 'offset' => [Pagination::offset('posts', 2), ['results' => [], 'count' => 0]];
    }

    #[Test]
    #[DataProvider('emptyFinalPages')]
    public function emptyFinalPageStopsAfterOneRequest(Pagination $pagination, array $field): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $transport->expects(self::once())->method('sendRequest')->willReturn(
            new Response(200, [], json_encode(['data' => ['posts' => $field]], JSON_THROW_ON_ERROR))
        );
        $client = new Client('https://example.test/graphql', [], [], $transport);

        self::assertSame([], iterator_to_array($client->paginate('query { posts }', $pagination)));
    }

    public static function malformedPages(): iterable
    {
        $pages = Pagination::lighthousePages('viewer.posts', 2);
        yield 'null data' => [$pages, ['data' => null], 'object in data'];
        yield 'missing parent' => [$pages, ['data' => (object) []], 'field is missing'];
        yield 'missing nested field' => [$pages, ['data' => ['viewer' => []]], 'field is missing'];
        yield 'scalar parent' => [$pages, ['data' => ['viewer' => 1]], 'field is missing'];
        yield 'null field' => [$pages, ['data' => ['viewer' => ['posts' => null]]], 'field must be an object'];
        foreach ([
            'missing list' => [[], "list at 'data'"],
            'object list' => [['data' => ['id' => 1]], "list at 'data'"],
            'scalar list' => [['data' => 'bad'], "list at 'data'"],
            'missing metadata' => [['data' => []], "object at 'paginatorInfo'"],
            'list metadata' => [['data' => [], 'paginatorInfo' => [true]], "object at 'paginatorInfo'"],
            'nonboolean flag' => [['data' => [], 'paginatorInfo' => ['hasMorePages' => 1]], "boolean at 'hasMorePages'"],
            'scalar record' => [['data' => [1], 'paginatorInfo' => ['hasMorePages' => false]], 'records must be objects'],
        ] as $name => [$field, $message]) {
            yield $name => [$pages, ['data' => ['viewer' => ['posts' => $field]]], $message];
        }
        $connection = Pagination::lighthouseConnection('posts', 2);
        foreach ([
            'missing edges' => [[], "list at 'edges'"],
            'object edges' => [['edges' => ['node' => ['id' => 1]]], "list at 'edges'"],
            'missing page info' => [['edges' => []], "object at 'pageInfo'"],
            'list page info' => [['edges' => [], 'pageInfo' => [true]], "object at 'pageInfo'"],
            'missing connection flag' => [['edges' => [], 'pageInfo' => []], "boolean at 'hasNextPage'"],
            'nonboolean connection flag' => [['edges' => [], 'pageInfo' => ['hasNextPage' => 'true']], "boolean at 'hasNextPage'"],
        ] as $name => [$field, $message]) {
            yield $name => [$connection, ['data' => ['posts' => $field]], $message];
        }
        foreach ([null, '', 1] as $cursor) {
            yield 'invalid cursor ' . var_export($cursor, true) => [$connection, ['data' => ['posts' => [
                'edges' => [['node' => ['id' => 1]]],
                'pageInfo' => ['hasNextPage' => true, 'endCursor' => $cursor],
            ]]], 'endCursor must advance'];
        }
        foreach ([null, 1, [], ['node' => null], ['node' => 'bad']] as $index => $edge) {
            yield 'invalid edge ' . $index => [$connection, ['data' => ['posts' => [
                'edges' => [$edge], 'pageInfo' => ['hasNextPage' => false],
            ]]], 'edges with object nodes'];
        }
        yield 'empty connection cannot advance' => [$connection, ['data' => ['posts' => [
            'edges' => [], 'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'next'],
        ]]], 'empty page'];
        foreach ([null, -1, '2', 2.5] as $count) {
            yield 'invalid count ' . var_export($count, true) => [Pagination::offset('posts', 2), ['data' => ['posts' => [
                'results' => [], 'count' => $count,
            ]]], 'count must be a non-negative integer'];
        }
        yield 'missing offset list' => [Pagination::offset('posts', 2), ['data' => ['posts' => ['count' => 0]]], "list at 'results'"];
        yield 'object offset list' => [Pagination::offset('posts', 2), ['data' => ['posts' => [
            'results' => ['id' => 1], 'count' => 1,
        ]]], "list at 'results'"];
        yield 'records exceed count' => [Pagination::offset('posts', 2), ['data' => ['posts' => [
            'results' => [['id' => 1], ['id' => 2]], 'count' => 1,
        ]]], 'more records than count'];
        yield 'empty offset cannot advance' => [Pagination::offset('posts', 2), ['data' => ['posts' => [
            'results' => [], 'count' => 1,
        ]]], 'empty page'];
        yield 'page overflow' => [Pagination::lighthousePages('posts', 2, PHP_INT_MAX), ['data' => ['posts' => [
            'data' => [['id' => 1]], 'paginatorInfo' => ['hasMorePages' => true],
        ]]], 'page number cannot advance'];
        yield 'offset overflow' => [Pagination::offset('posts', 2, PHP_INT_MAX), ['data' => ['posts' => [
            'results' => [['id' => 1]], 'count' => PHP_INT_MAX,
        ]]], 'offset cannot advance'];
    }

    #[Test]
    #[DataProvider('malformedPages')]
    public function rejectsMalformedPageBeforeYieldingRecords(Pagination $pagination, array $response, string $message): void
    {
        $transport = $this->createMock(ClientInterface::class);
        $transport->expects(self::once())->method('sendRequest')->willReturn(
            new Response(200, [], json_encode($response, JSON_THROW_ON_ERROR))
        );
        $client = new Client('https://example.test/graphql', [], [], $transport);
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);
        foreach ($client->paginate('query { posts }', $pagination) as $record) {
            self::fail('Malformed pages must be rejected before yielding records');
        }
    }
}
