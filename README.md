# PHP GraphQL Client

[![CI](https://img.shields.io/github/actions/workflow/status/darthsoup/php-graphql-client/php.yml?branch=main&label=CI&style=flat-square)](https://github.com/darthsoup/php-graphql-client/actions/workflows/php.yml)
[![Latest Version](https://img.shields.io/packagist/v/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![PHP Version](https://img.shields.io/packagist/php-v/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![Total Downloads](https://img.shields.io/packagist/dt/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)
[![License](https://img.shields.io/packagist/l/darthsoup/php-graphql-client?style=flat-square)](https://packagist.org/packages/darthsoup/php-graphql-client)

A PHP 8.3+ GraphQL client with a fluent query builder. Interact with any
GraphQL API without writing raw query strings — compose type-safe queries and
mutations in pure PHP, then run them against any GraphQL endpoint.

# Requirements

- PHP **8.3** or higher
- `ext-json`
- A PSR-18 compatible HTTP client

# Installation

```
composer require darthsoup/php-graphql-client
```

# Usage

There are two primary ways to build GraphQL queries:

1. **Query class** — direct, concise object representation of a GraphQL query.
2. **QueryBuilder class** — fluent builder for constructing queries dynamically.

# Query Examples

## Simple Query

```php
$gql = (new Query('companies'))
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
```

This simple query will retrieve all companies displaying their names and serial
numbers.

### The Full Form

The query provided in the previous example is represented in the
"shorthand form". The shorthand form involves writing a reduced number of code
lines which speeds up the process of wriing querries. Below is an example of
the full form for the exact same query written in the previous example.

```php
$gql = (new Query())
    ->setSelectionSet(
        [
            (new Query('companies'))
                ->setSelectionSet(
                    [
                        'name',
                        'serialNumber'
                    ]
                )
        ]
    );
```

As seen in the example, the shorthand form is simpler to read and write, it's
generally preferred to use compared to the full form.

The full form shouldn't be used unless the query can't be represented in the
shorthand form, which has only one case, when we want to run multiple queries
in the same object.


## Multiple Queries
```php
$gql = (new Query())
    ->setSelectionSet(
        [
            (new Query('companies'))
            ->setSelectionSet(
                [
                    'name',
                    'serialNumber'
                ]
            ),
            (new Query('countries'))
            ->setSelectionSet(
                [
                    'name',
                    'code',
                ]
            )
        ]
    );
```

This query retrieves all companies and countries displaying some data fields
for each. It basically runs two (or more if needed) independent queries in
one query object envelop.

Writing multiple queries requires writing the query object in the full form
to represent each query as a subfield under the parent query object.

## Nested Queries
```php
$gql = (new Query('companies'))
    ->setSelectionSet(
        [
            'name',
            'serialNumber',
            (new Query('branches'))
                ->setSelectionSet(
                    [
                        'address',
                        (new Query('contracts'))
                            ->setSelectionSet(['date'])
                    ]
                )
        ]
    );
```

This query is a more complex one, retrieving not just scalar fields, but object
fields as well. This query returns all companies, displaying their names, serial
numbers, and for each company, all its branches, displaying the branch address,
and for each address, it retrieves all contracts bound to this address,
displaying their dates.

## Query With Arguments

```php
$gql = (new Query('companies'))
    ->setArguments(['name' => 'Tech Co.', 'first' => 3])
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
```

This query does not retrieve all companies by adding arguments. This query will
retrieve the first 3 companies with the name "Tech Co.", displaying their names
and serial numbers.

## Query With Array Argument

```php
$gql = (new Query('companies'))
    ->setArguments(['serialNumbers' => [159, 260, 371]])
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
```

This query is a special case of the arguments query. In this example, the query
will retrieve only the companies with serial number in one of 159, 260, and 371,
displaying the name and serial number.

## Query With Input Object Argument

```php
$gql = (new Query('companies'))
    ->setArguments(['filter' => new RawObject('{name_starts_with: "Face"}')])
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
```

This query is another special case of the arguments query. In this example,
we're setting a custom input object "filter" with some values to limit the
companies being returned. We're setting the filter "name_starts_with" with
value "Face".  This query will retrieve only the companies whose names
start with the phrase "Face".

The RawObject class being constructed is used for injecting the string into the
query as it is. Whatever string is input into the RawObject constructor will be
put in the query as it is without any custom formatting normally done by the
query class.

## Query With Variables

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
```

This query shows how variables can be used in this package to allow for dynamic
requests enabled by GraphQL standards.

### The Variable Class

The Variable class is an immutable class that represents a variable in GraphQL
standards. Its constructor receives 4 arguments:

- name: Represents the variable name
- type: Represents the variable type according to the GraphQL server schema
- isRequired (Optional): Represents if the variable is required or not, it's
false by default
- defaultValue (Optional): Represents the default value to be assigned to the
variable. The default value will only be considered
if the isRequired argument is set to false.

## Using an alias
```php
$gql = (new Query())
    ->setSelectionSet(
        [
            (new Query('companies', 'TechCo'))
                ->setArguments(['name' => 'Tech Co.'])
                ->setSelectionSet(
                    [
                        'name',
                        'serialNumber'
                    ]
                ),
            (new Query('companies', 'AnotherTechCo'))
                ->setArguments(['name' => 'A.N. Other Tech Co.'])
                ->setSelectionSet(
                    [
                        'name',
                        'serialNumber'
                    ]
                )
        ]
    );
```

An alias can be set in the second argument of the Query constructor for occasions when the same object needs to be retrieved multiple times with different arguments.

```php
$gql = (new Query('companies'))
    ->setAlias('CompanyAlias')
    ->setSelectionSet(
        [
            'name',
            'serialNumber'
        ]
    );
```

The alias can also be set via the setter method.

## Using Interfaces: Query With Inline Fragments

When querying a field that returns an interface type, you might need to use
inline fragments to access data on the underlying concrete type.

This example show how to generate inline fragments using this package:

```php
$gql = new Query('companies');
$gql->setSelectionSet(
    [
        'serialNumber',
        'name',
        (new InlineFragment('PrivateCompany'))
            ->setSelectionSet(
                [
                    'boardMembers',
                    'shareholders',
                ]
            ),
    ]
);
```

# The Query Builder

The QueryBuilder class can be used to construct Query objects dynamically, which
can be useful in some cases. It works very similarly to the Query class, but the
Query building is divided into steps.

That's how the "Query With Input Object Argument" example can be created using
the QueryBuilder:

```php
$builder = (new QueryBuilder('companies'))
    ->setVariable('namePrefix', 'String', true)
    ->setArgument('filter', new RawObject('{name_starts_with: $namePrefix}'))
    ->selectField('name')
    ->selectField('serialNumber');
$gql = $builder->getQuery();
```

As with the Query class, an alias can be set using the second constructor argument.

```php
$builder = (new QueryBuilder('companies', 'CompanyAlias'))
    ->selectField('name')
    ->selectField('serialNumber');

$gql = $builder->getQuery();
```

Or via the setter method

```php
$builder = (new QueryBuilder('companies'))
    ->setAlias('CompanyAlias')
    ->selectField('name')
    ->selectField('serialNumber');

$gql = $builder->getQuery();
```

### The Full Form

Just like the Query class, the QueryBuilder class can be written in full form to
enable writing multiple queries under one query builder object. Below is an
example for how the full form can be used with the QueryBuilder:

```php
$builder = (new QueryBuilder())
    ->setVariable('namePrefix', 'String', true)
    ->selectField(
        (new QueryBuilder('companies'))
            ->setArgument('filter', new RawObject('{name_starts_with: $namePrefix}'))
            ->selectField('name')
            ->selectField('serialNumber')
    )
    ->selectField(
        (new QueryBuilder('company'))
            ->setArgument('serialNumber', 123)
            ->selectField('name')
    );
$gql = $builder->getQuery();
```

This query is an extension to the query in the previous example. It returns all
companies starting with a name prefix and returns the company with the
`serialNumber` of value 123, both in the same response.

# Constructing The Client

A Client object can easily be instantiated by providing the GraphQL endpoint
URL. 

The Client constructor also receives an optional "authorizationHeaders"
array, which can be used to add authorization headers to all requests being sent
to the GraphQL server.

Example:

```php
$client = new Client(
    'http://api.graphql.com',
    ['Authorization' => 'Basic xyz']
);
```


The third argument accepts an `httpOptions` array. Only the `headers` key is
processed — it is merged with `authorizationHeaders`:

```php
$client = new Client(
    'http://api.graphql.com',
    [],
    [
        'headers' => [
            'Authorization' => 'Basic xyz',
            'User-Agent'    => 'my-app/1.0',
        ],
    ]
);
```

You can also inject your own preconfigured [PSR-18](https://www.php-fig.org/psr/psr-18/)
HTTP client as the fourth argument. The client uses `php-http/discovery` to
auto-discover one when none is provided:

```php
$client = new Client(
    'http://api.graphql.com',
    [],
    [],
    $myPsr18HttpClient
);
```

# Running Queries

## Result Formatting

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

## Passing Variables to Queries

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
$variablesArray = ['name' => 'Tech Co.', 'first' => 5];
$results = $client->runQuery($gql, true, $variablesArray);
```

# Mutations

Mutations follow the same rules of queries in GraphQL, they select fields on
returned objects, receive arguments, and can have sub-fields.

Here's a sample example on how to construct and run mutations:

```php
$mutation = (new Mutation('createCompany'))
    ->setArguments(['companyObject' => new RawObject('{name: "Trial Company", employees: 200}')])
    ->setSelectionSet(
        [
            '_id',
            'name',
            'serialNumber',
        ]
    );
$results = $client->runQuery($mutation);
```

Mutations can be run by the client the same way queries are run.

## Mutations With Variables Example

Mutations can utilize the variables in the same way Queries can. Here's an
example on how to use the variables to pass input objects to the GraphQL server
dynamically:

```php
$mutation = (new Mutation('createCompany'))
    ->setVariables([new Variable('company', 'CompanyInputObject', true)])
    ->setArguments(['companyObject' => '$company']);

$variables = ['company' => ['name' => 'Tech Company', 'type' => 'Testing', 'size' => 'Medium']];
$client->runQuery(
    $mutation, true, $variables
);
```

These are the resulting mutation and the variables passed with it:

```php
mutation($company: CompanyInputObject!) {
  createCompany(companyObject: $company)
}
{"company":{"name":"Tech Company","type":"Testing","size":"Medium"}}
```

# Examples

Runnable examples for queries, mutations, the query builder, and raw queries
can be found in the [`examples/`](examples/) directory:

| File | Description |
|------|-------------|
| [`query_example.php`](examples/query_example.php) | Basic and nested queries |
| [`query_builder_example.php`](examples/query_builder_example.php) | Building queries dynamically |
| [`mutation_example.php`](examples/mutation_example.php) | Creating and running mutations |
| [`raw_query_example.php`](examples/raw_query_example.php) | Running raw GraphQL strings |

# Running Raw Queries

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

# Laravel Integration

The package has no Laravel-specific code, but you can wire it into the service
container with a simple service provider.

## Service Provider

```php
<?php

namespace App\Providers;

use GraphQL\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class GraphQLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function (Application $app): Client {
            return new Client(
                config('graphql.endpoint'),
                ['Authorization' => 'Bearer ' . config('graphql.token')],
            );
        });
    }
}
```

Register it in `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\GraphQLServiceProvider::class,
];
```

Add a `config/graphql.php` file:

```php
<?php

return [
    'endpoint' => env('GRAPHQL_ENDPOINT', 'https://api.example.com/graphql'),
    'token'    => env('GRAPHQL_TOKEN'),
];
```

## Usage

Resolve the client via dependency injection:

```php
use GraphQL\Client;
use GraphQL\Query;

class CompanyController extends Controller
{
    public function __construct(private readonly Client $graphql) {}

    public function index(): array
    {
        $gql = (new Query('companies'))
            ->setSelectionSet(['name', 'serialNumber']);

        return $this->graphql->runQuery($gql, true)->getData();
    }
}
```

Or resolve it from the container directly:

```php
$client = app(GraphQL\Client::class);
```

# Contributing

```bash
composer install
vendor/bin/phpunit          # run tests
vendor/bin/phpstan analyse  # static analysis
```

Pull requests are welcome. Please ensure both commands pass before submitting.
