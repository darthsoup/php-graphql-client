<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\QueryBuilder\QueryBuilder;

// Create Client object to contact the GraphQL endpoint
$client = new Client(
    'https://graphql-pokeapi.graphcdn.app/',
    []  // Replace with array of extra headers to be sent with request for auth or other purposes
);

// Create the GraphQL query
$builder = (new QueryBuilder('pokemon'))
    ->setArgument('name', 'pikachu')
    ->selectField('id')
    ->selectField('name')
    ->selectField(
        (new QueryBuilder('sprites'))
            ->selectField('front_default')
    )
    ->selectField(
        (new QueryBuilder('moves'))
            ->selectField(
                (new QueryBuilder('move'))
                    ->selectField('name')
            )
    );

// Run query to get results
try {
    $results = $client->runQuery($builder);
}
catch (QueryError $exception) {

    // Catch query error and display error details
    print_r($exception->getErrorDetails());
    exit;
}

// Display original response from endpoint
var_dump($results->getResponseObject());

// Display part of the returned results of the object
var_dump($results->getData()->pokemon);

// Reformat the results to an array and get the results of part of the array
$results->reformatResults(true);
var_dump($results->getData()['pokemon']);
