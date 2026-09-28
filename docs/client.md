# Client and results

## Constructing the client

A Client object can easily be instantiated by providing the GraphQL endpoint
URL.

The Client constructor also receives an optional "authorizationHeaders"
array, which can be used to add authorization headers to all requests being sent
to the GraphQL server.

Example:

```php
$client = new Client(
    'https://api.graphql.com',
    ['Authorization' => 'Basic xyz']
);
```


The Client constructor also receives an optional "httpOptions" array, which
**overrides** the "authorizationHeaders" and can be used to add custom
[Guzzle HTTP Client request options](https://guzzle.readthedocs.io/en/latest/request-options.html).
Redirects are disabled by default so a redirected request cannot forward query
variables to another URL. Set `allow_redirects` explicitly if the endpoint
requires redirects, and only do so for endpoints whose redirect targets you trust.

Example:

```php
$client = new Client(
    'https://api.graphql.com',
    [],
    [
        'connect_timeout' => 5,
        'timeout' => 5,
        'headers' => [
            'Authorization' => 'Basic xyz',
            'User-Agent' => 'testing/1.0',
        ],
        'proxy' => [
                'http'  => 'tcp://localhost:8125', // Use this proxy with "http"
                'https' => 'tcp://localhost:9124', // Use this proxy with "https",
                'no' => ['.mit.edu', 'foo.com']    // Don't use a proxy with these
        ],
        'cert' => ['/path/server.pem', 'password'],
    ]
);
```


It is possible to use your own preconfigured HTTP client that implements the [PSR-18 interface](https://www.php-fig.org/psr/psr-18/).

Example:

```php
$client = new Client(
    'https://api.graphql.com',
    [],
    [],
    $myHttpClient
);
```

## Running queries

### Result formatting

Running query with the GraphQL client and getting the results in object
structure:

```php
$results = $client->runQuery($gql);
$results->getData()->companies[0]->branches;
```
Or getting results in array structure:

```php
$results = $client->runQuery($gql, true);
$results->getData()['companies'][1]['branches']['address'];
```

`getData()` returns `null` when the response contains `"data": null`. Invalid
JSON or a response without `data` or `errors` throws `InvalidResponseException`.
When GraphQL returns errors, `QueryError` exposes every error through
`getErrors()`, partial data through `getData()`, and the full decoded response
through `getResponseData()`.
Guzzle normally raises an HTTP exception for error statuses. Any non-success
response returned normally raises `InvalidResponseException`. HTTP 400 GraphQL
errors still raise `QueryError`.

### Passing variables to queries

Running queries containing variables requires passing an associative array which
maps variable names (keys) to variable values (values) to the `runQuery` method.
Here's an example:

```php
$gql = (new Query('companies'))
    ->setVariables(
        [
            new Variable('name', 'String', true),
            new Variable('limit', 'Int', false, 5)
        ]
    )
    ->setArguments(['name' => '$name', 'first' => '$limit'])
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
$variablesArray = ['name' => 'Tech Co.', 'limit' => 5];
$results = $client->runQuery($gql, true, $variablesArray);
```

## Running raw queries

Although not the primary goal of this package, but it supports running raw
string queries, just like any other client using the `runRawQuery` method in the
`Client` class. Here's an example on how to use it:

```php
$gql = <<<QUERY
query {
    pokemon(name: "Pikachu") {
        id
        number
        name
        attacks {
            special {
                name
                type
                damage
            }
        }
    }
}
QUERY;

$results = $client->runRawQuery($gql);
```
