# Query Examples

The builder validates field, alias, operation, argument, variable, and fragment
type names as GraphQL names. Selection set strings and `RawObject` insert raw
GraphQL syntax; use them only with trusted query text. Pass user supplied values
as arguments or GraphQL variables rather than incorporating them into names or
raw syntax.

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

For values built from PHP data, use `InputObject` instead. Its fields can contain
other `InputObject` instances, lists, or `VariableReference` instances:

```php
$gql = (new Query('companies'))
    ->setArguments([
        'filter' => new InputObject([
            'name_starts_with' => new VariableReference('prefix'),
            'options' => new InputObject(['active' => true]),
        ]),
    ])
    ->setSelectionSet(['name']);
```

Strings matching `$variableName` still render as variable references for
compatibility. Use `VariableReference` in new code to make that intent explicit.

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

## Directives and reusable fragments

```php
$fields = (new FragmentDefinition('CompanyFields', 'Company'))
    ->setSelectionSet(['name', 'serialNumber']);

$gql = (new Query('company'))
    ->setArguments(['id' => new VariableReference('id')])
    ->setDirectives([new Directive('include', ['if' => new VariableReference('show')])])
    ->setSelectionSet([new FragmentSpread('CompanyFields')])
    ->setFragmentDefinitions([$fields]);
```

`setDirectives()` also works on root queries and inline fragments. A
`FragmentSpread` can have directives of its own.
