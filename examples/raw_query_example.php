<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Exception\QueryError;

// Create Client object to contact the GraphQL endpoint
$client = new Client(
    'https://graphql-pokeapi.graphcdn.app/',
    []  // Replace with array of extra headers to be sent with request for auth or other purposes
);

$gql = <<<QUERY
query {
    pokemon(name: "ditto") {
        id
		name
		sprites {
			front_default
		}
		moves {
			move {
				name
			}
		}
		types {
			type {
				name
			}
		}
    }
}
QUERY;

// Run query to get results
try {
    $results = $client->runRawQuery($gql);
}
catch (QueryError $exception) {

    // Catch query error and desplay error details
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
