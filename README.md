# PHP GraphQL Client

[![CI](https://img.shields.io/github/actions/workflow/status/darthsoup/php-graphql-client/php.yml?branch=main&label=CI&style=flat-square)](https://github.com/darthsoup/php-graphql-client/actions/workflows/php.yml)
[![Latest Version](https://img.shields.io/packagist/v/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![PHP Version](https://img.shields.io/packagist/php-v/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![Total Downloads](https://img.shields.io/packagist/dt/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![License](https://img.shields.io/packagist/l/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)

A PHP 8.3+ GraphQL client with a fluent query builder. Interact with any
GraphQL API without writing raw query strings — compose type-safe queries and
mutations in pure PHP, then run them against any GraphQL endpoint.

## Requirements

- PHP 8.3 or higher
- `ext-json`

## Installation

```bash
composer require darthsoup/php-graphql-client
```

## Quick start

```php
require __DIR__ . '/vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Query;

$client = new Client('https://example.com/graphql');
$query = (new Query('companies'))->setSelectionSet(['name', 'serialNumber']);
$data = $client->runQuery($query)->getData();
```

The client uses Guzzle by default. You can also pass a PSR-18 HTTP client. GraphQL
errors throw `GraphQL\Exception\QueryError` and expose the returned errors and
partial data.

## Documentation

- [Build queries](docs/queries.md): fields, arguments, variables, aliases, fragments, and directives
- [Query builder](docs/query-builder.md): fluent and dynamic query construction
- [Client and results](docs/client.md): configuration, execution, variables, and raw queries
- [Pagination](docs/pagination.md): lazy iteration across Lighthouse and offset pages
- [Mutations](docs/mutations.md): constructing and running mutations
- [Error reporting](docs/error-reporting.md): exceptions and safe reporting fields

## Examples

The [`examples/`](examples/) directory contains runnable PHP examples:

- [Queries](examples/query_example.php)
- [Query builder](examples/query_builder_example.php)
- [Directives](examples/directives_example.php)
- [Lighthouse pagination](examples/lighthouse_pagination_example.php)
- [Pokémon pagination](examples/pokemon_pagination_example.php)
- [Mutations](examples/mutation_example.php)
- [Raw queries](examples/raw_query_example.php)

## Contributing

Run `composer install`, `composer test`, `composer phpstan`, and
`composer style:check` before opening a pull request. Integration tests use an
external API: `composer test:integration`.
