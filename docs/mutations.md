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

## Operation abstraction

`Query` and `Mutation` are sibling classes extending `AbstractOperation`. The base
class owns names, variables, arguments, selection sets, directives, fragments,
and rendering. Each concrete class supplies its operation keyword.

Both work with `Client::runQuery()` and the existing fluent methods. Use
`AbstractOperation` for parameters or return types that accept either operation:

```php
use GraphQL\AbstractOperation;

function renderOperation(AbstractOperation $operation): string
{
    return (string) $operation;
}
```

This is a breaking type change: `Mutation` no longer extends `Query`.
Replace `Query` type declarations and `instanceof Query` checks that are intended
to include mutations with `AbstractOperation`. `QueryBuilderInterface::getQuery()`
now returns `AbstractOperation`; implementations returning `Query` remain valid
through covariance. `MutationBuilder::getMutation()` still returns `Mutation`.
