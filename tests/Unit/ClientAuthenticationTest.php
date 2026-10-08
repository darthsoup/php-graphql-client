<?php

declare(strict_types=1);

namespace GraphQL\Tests\Unit;

use GraphQL\Auth\AuthInterface;
use GraphQL\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

#[CoversClass(Client::class)]
final class ClientAuthenticationTest extends TestCase
{
    #[Test]
    public function authenticationReceivesCompletedRequestAndTransportUsesReturnedRequest(): void
    {
        $auth = $this->createMock(AuthInterface::class);
        $transport = $this->createMock(ClientInterface::class);
        $signedRequest = null;
        $auth->expects(self::once())->method('run')
            ->with(self::callback(static function (RequestInterface $request): bool {
                self::assertSame('QUERY', $request->getMethod());
                self::assertSame('https://example.test/graphql', (string) $request->getUri());
                self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
                self::assertSame('original', $request->getHeaderLine('Authorization'));
                self::assertSame('custom', $request->getHeaderLine('X-Custom'));
                self::assertSame([
                    'query' => 'query { posts }',
                    'variables' => ['status' => 'active'],
                ], json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR));

                return true;
            }), ['timeout' => 3, 'region' => 'test-region'])
            ->willReturnCallback(static function (RequestInterface $request) use (&$signedRequest): RequestInterface {
                $signedRequest = $request->withHeader('Authorization', 'signed');

                return $signedRequest;
            });
        $transport->expects(self::once())->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request) use (&$signedRequest): bool {
                self::assertSame($signedRequest, $request);
                self::assertSame('signed', $request->getHeaderLine('Authorization'));

                return true;
            }))
            ->willReturn(new Response(200, [], '{"data":{"ok":true}}'));
        $client = new Client(
            'https://example.test/graphql',
            ['Authorization' => 'original'],
            ['headers' => ['X-Custom' => 'custom'], 'timeout' => 3, 'region' => 'test-region'],
            $transport,
            'QUERY',
            $auth
        );

        self::assertSame(['ok' => true], $client->runRawQuery('query { posts }', true, ['status' => 'active'])->getData());
    }

    #[Test]
    public function authenticationFailurePropagatesWithoutSendingRequest(): void
    {
        $failure = new RuntimeException('Unable to authenticate');
        $auth = $this->createMock(AuthInterface::class);
        $auth->expects(self::once())->method('run')->willThrowException($failure);
        $transport = $this->createMock(ClientInterface::class);
        $transport->expects(self::never())->method('sendRequest');
        $client = new Client('https://example.test/graphql', [], [], $transport, 'POST', $auth);

        try {
            $client->runRawQuery('query { posts }');
            self::fail('Expected authentication failure');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }
    }
}
