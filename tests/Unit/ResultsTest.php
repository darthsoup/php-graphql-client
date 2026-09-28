<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Exception\QueryError;
use GraphQL\Exception\InvalidResponseException;
use GraphQL\Results;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Results::class)]
final class ResultsTest extends TestCase
{
    protected Client $client;

    protected MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->client      = new Client(['handler' => $this->mockHandler]);
    }

    #[Test]
    public function testGetSuccessResponseAsObject(): void
    {
        $body = json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ],
                    [
                        'data' => 'value',
                    ]
                ]
            ]
        ]);
        $response = new Response(200, [], $body);
        $this->mockHandler->append($response);

        $response = $this->client->post('', []);
        $results  = new Results($response);

        $this->assertEquals($response, $results->getResponseObject());
        $this->assertSame($body, $results->getResponseBody());

        $object = new stdClass();
        $object->data = new stdClass();
        $object->data->someField = [];
        $object->data->someField[] = new stdClass();
        $object->data->someField[] = new stdClass();
        $object->data->someField[0]->data = 'value';
        $object->data->someField[1]->data = 'value';
        $this->assertEquals(
            $object,
            $results->getResults()
        );
        $this->assertEquals(
            $object->data,
            $results->getData()
        );
    }

    #[Test]
    public function testGetSuccessResponseAsArray(): void
    {
        $body = json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ],
                    [
                        'data' => 'value',
                    ]
                ]
            ]
        ]);
        $originalResponse = new Response(200, [], $body);
        $this->mockHandler->append($originalResponse);

        $response = $this->client->post('', []);
        $results  = new Results($response, true);

        $this->assertEquals($originalResponse, $results->getResponseObject());
        $this->assertSame($body, $results->getResponseBody());
        $this->assertEquals(
            [
                'data' => [
                    'someField' => [
                        [
                            'data' => 'value',
                        ],
                        [
                            'data' => 'value',
                        ]
                    ]
                ]
            ],
            $results->getResults()
        );
        $this->assertEquals(
            [
                'someField' => [
                        [
                            'data' => 'value',
                        ],
                        [
                            'data' => 'value',
                        ]
                    ]
            ],
            $results->getData()
        );
    }

    #[Test]
    public function testGetQueryInvalidSyntaxError(): void
    {
        $body = json_encode([
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
        ]);
        $originalResponse = new Response(200, [], $body);
        $this->mockHandler->append($originalResponse);

        $response = $this->client->post('', []);
        $this->expectException(QueryError::class);
        new Results($response);
    }

    #[Test]
    public function testRejectsMalformedResponse(): void
    {
        $response = new Response(200, [], 'not json');

        try {
            new Results($response);
            self::fail('Expected an invalid response exception');
        } catch (InvalidResponseException $exception) {
            self::assertSame($response, $exception->getResponse());
        }
    }

    #[Test]
    public function testRejectsResponseWithoutDataOrErrors(): void
    {
        $this->expectException(InvalidResponseException::class);
        new Results(new Response(200, [], '{}'));
    }

    #[Test]
    public function testReturnsNullData(): void
    {
        $results = new Results(new Response(200, [], '{"data":null}'));
        self::assertNull($results->getData());
    }

    #[Test]
    public function testRejectsInvalidDataShape(): void
    {
        $this->expectException(InvalidResponseException::class);
        new Results(new Response(200, [], '{"data":"unexpected"}'));
    }

    #[Test]
    public function testArrayFormattingPreservesObjectAndListShapes(): void
    {
        $results = new Results(new Response(200, [], '{"data":{"empty":{},"items":[{"id":1}]}}'), true);

        self::assertSame(['empty' => [], 'items' => [['id' => 1]]], $results->getData());

        $this->expectException(InvalidResponseException::class);
        new Results(new Response(200, [], '{"data":[]}'), true);
    }

    #[Test]
    public function testReadsBodyEvenWhenStreamCursorHasMoved(): void
    {
        $response = new Response(200, [], '{"data":{"ok":true}}');
        $response->getBody()->getContents();

        $results = new Results($response, true);
        self::assertSame(['ok' => true], $results->getData());
    }

    #[Test]
    public function testPreservesAllErrorsAndPartialData(): void
    {
        $response = new Response(200, [], json_encode([
            'data' => ['field' => 'partial'],
            'errors' => [['message' => 'first'], ['message' => 'second']],
        ], JSON_THROW_ON_ERROR));

        try {
            new Results($response);
            self::fail('Expected a query error');
        } catch (QueryError $exception) {
            self::assertSame('first', $exception->getMessage());
            self::assertSame($response, $exception->getResponseObject());
            self::assertCount(2, $exception->getErrors());
            self::assertSame(['field' => 'partial'], $exception->getData());
            self::assertSame(['field' => 'partial'], $exception->getResponseData()['data']);
        }
    }

    #[Test]
    public function testArrayResultsPreserveGraphqlErrors(): void
    {
        $response = new Response(200, [], '{"data":{"field":"partial"},"errors":[{"message":"failed"}]}');

        try {
            new Results($response, true);
            self::fail('Expected a query error');
        } catch (QueryError $exception) {
            self::assertSame('failed', $exception->getMessage());
            self::assertSame(['field' => 'partial'], $exception->getData());
        }
    }

    #[Test]
    public function testReformatResultsFromObjectToArray(): void
    {
        $body = json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ],
                    [
                        'data' => 'value',
                    ]
                ]
            ]
        ]);
        $originalResponse = new Response(200, [], $body);
        $this->mockHandler->append($originalResponse);

        $response = $this->client->post('', []);
        $results  = new Results($response);
        $results->reformatResults(true);

        $this->assertEquals(
            [
                'data' => [
                    'someField' => [
                        [
                            'data' => 'value',
                        ],
                        [
                            'data' => 'value',
                        ]
                    ]
                ]
            ],
            $results->getResults()
        );
        $this->assertEquals(
            [
                'someField' => [
                    [
                        'data' => 'value',
                    ],
                    [
                        'data' => 'value',
                    ]
                ]
            ],
            $results->getData()
        );
    }

    #[Test]
    public function testReformatResultsFromArrayToObject(): void
    {
        $body = json_encode([
            'data' => [
                'someField' => [
                    [
                        'data' => 'value',
                    ],
                    [
                        'data' => 'value',
                    ]
                ]
            ]
        ]);
        $originalResponse = new Response(200, [], $body);
        $this->mockHandler->append($originalResponse);

        $response = $this->client->post('', []);
        $results  = new Results($response, true);
        $results->reformatResults(false);

        $object = new stdClass();
        $object->data = new stdClass();
        $object->data->someField = [];
        $object->data->someField[] = new stdClass();
        $object->data->someField[] = new stdClass();
        $object->data->someField[0]->data = 'value';
        $object->data->someField[1]->data = 'value';
        $this->assertEquals(
            $object,
            $results->getResults()
        );
        $this->assertEquals(
            $object->data,
            $results->getData()
        );
    }
}
