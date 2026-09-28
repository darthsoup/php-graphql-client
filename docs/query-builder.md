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

## The Full Form

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
