<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Pagination;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// This PSR-18 client simulates two Lighthouse pages, so the example runs offline.
$httpClient = new class () implements ClientInterface {
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $variables = $body['variables'];

        if (array_key_exists('page', $variables)) {
            $page = $variables['page'];
            $posts = $page === 1 ? [['id' => '1'], ['id' => '2']] : [['id' => '3']];
            $data = ['posts' => [
                'data' => $posts,
                'paginatorInfo' => ['hasMorePages' => $page === 1],
            ]];
        } else {
            $after = $variables['after'];
            $nodes = $after === null ? [['id' => '1'], ['id' => '2']] : [['id' => '3']];
            $data = ['posts' => [
                'edges' => array_map(static fn (array $node): array => ['node' => $node], $nodes),
                'pageInfo' => [
                    'hasNextPage' => $after === null,
                    'endCursor' => $after === null ? 'cursor-2' : null,
                ],
            ]];
        }

        return new Response(200, [], json_encode(['data' => $data], JSON_THROW_ON_ERROR));
    }
};

$client = new Client('https://example.test/graphql', [], [], $httpClient);

// lighthousePages() works for both PAGINATOR and SIMPLE @paginate fields.
$pageQuery = <<<'GRAPHQL'
query Posts($first: Int!, $page: Int) {
  posts(first: $first, page: $page) {
    data { id }
    paginatorInfo { hasMorePages }
  }
}
GRAPHQL;
foreach ($client->paginate($pageQuery, Pagination::lighthousePages('posts', 2)) as $post) {
    echo 'Page mode: ', $post['id'], PHP_EOL;
}

$connectionQuery = <<<'GRAPHQL'
query Posts($first: Int!, $after: String) {
  posts(first: $first, after: $after) {
    edges { node { id } }
    pageInfo { hasNextPage endCursor }
  }
}
GRAPHQL;
foreach ($client->paginate($connectionQuery, Pagination::lighthouseConnection('posts', 2)) as $post) {
    echo 'Connection mode: ', $post['id'], PHP_EOL;
}
