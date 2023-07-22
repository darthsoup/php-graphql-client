<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;

// Create Client object to contact the GraphQL endpoint
$client = new Client(
    'https://graphql-pokeapi.graphcdn.app/',
    []  // Replace with array of extra headers to be sent with request for auth or other purposes
);


// Create the GraphQL query
$gql = (new Query('pokemon'))
    ->setArguments(['name' => 'pikachu'])
    ->setSelectionSet(
        [
            'id',
            'name',
            (new Query('sprites'))
                ->setSelectionSet(
                    [
                       'front_default'
                    ]
                ),
            (new Query('moves'))
                ->setSelectionSet(
                    [
                        (new Query('move'))
                            ->setSelectionSet(
                                [
                                    'name'
                                ]
                            ),
                    ]
                ),
        ]
    );

// Run query to get results
try {
    $results = $client->runQuery($gql);
}
catch (QueryError $exception) {

    // Catch query error and dispaly error details
    print_r($exception->getErrorDetails());
    exit;
}

// Display original response from endpoint
var_dump($results->getResponseObject());

// Display part of the returned results of the object
var_dump($results->getData());

// Reformat the results to an array and get the results of part of the array
$results->reformatResults(true);
var_dump($results->getData()['pokemon']);
