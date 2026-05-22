<?php

namespace GraphQL\Tests\Util;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleAdapter implements Psr18ClientInterface
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->client->send($request);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return $e->getResponse();
            }

            throw new class($e->getMessage(), $request, $e) extends \RuntimeException implements NetworkExceptionInterface {
                public function __construct(
                    string $message,
                    private readonly RequestInterface $psrRequest,
                    \Throwable $previous,
                ) {
                    parent::__construct($message, 0, $previous);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->psrRequest;
                }
            };
        }
    }
}
