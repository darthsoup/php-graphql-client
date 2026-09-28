<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Pagination;

$client = new Client('https://graphql-pokeapi.graphcdn.app/', [], ['connect_timeout' => 5, 'timeout' => 15]);
$query = <<<'GRAPHQL'
query Pokemons($limit: Int!, $offset: Int!) {
  pokemons(limit: $limit, offset: $offset) {
    count
    results { name image }
  }
}
GRAPHQL;

// Only two pages of five records are requested because iteration stops early.
$shown = 0;
foreach ($client->paginate($query, Pagination::offset('pokemons', 5)) as $pokemon) {
    echo $pokemon['name'], ': ', $pokemon['image'], PHP_EOL;
    if (++$shown === 10) {
        break;
    }
}
