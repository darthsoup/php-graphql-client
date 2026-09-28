# Pagination

GraphQL does not define a pagination format. Lighthouse's `@paginate` is a
server-side schema directive; client queries use the fields and arguments it
creates. `Client::paginate()` fetches pages lazily and yields each record as an
associative array. It accepts a `Query`, `QueryBuilder`, or raw query string.

For Lighthouse `PAGINATOR` and `SIMPLE`, select `data` and
`paginatorInfo { hasMorePages }`:

```php
use GraphQL\Pagination;

$query = <<<'GRAPHQL'
query Posts($first: Int!, $page: Int) {
  posts(first: $first, page: $page) {
    data { id title }
    paginatorInfo { hasMorePages }
  }
}
GRAPHQL;

foreach ($client->paginate($query, Pagination::lighthousePages('posts', 20)) as $post) {
    echo $post['title'], PHP_EOL;
}
```

For Lighthouse `CONNECTION`, select `edges { node { ... } }` and
`pageInfo { hasNextPage endCursor }`. Use
`Pagination::lighthouseConnection('posts', 20)` with a query that declares
`$first: Int!` and `$after: String`.

For the Pokémon API's `limit`/`offset` format, select `count` and `results`
and use `Pagination::offset('pokemons', 20)` with a query that declares
`$limit: Int!` and `$offset: Int!`.

The field path names the response field below GraphQL `data`; nested paths such
as `viewer.posts` and aliases work. The factories also accept a starting page,
cursor, or offset as their third argument. Other query variables stay fixed,
while the client supplies pagination variables on each request. Do not include
those variables in the separate `$variables` array.

No request is sent until iteration begins. Breaking the loop prevents further
requests. GraphQL and transport exceptions propagate as usual; missing page
metadata or a page that cannot advance throws `UnexpectedValueException`.

See the runnable [Lighthouse example](../examples/lighthouse_pagination_example.php)
(offline) and [Pokémon example](../examples/pokemon_pagination_example.php)
(live endpoint).
