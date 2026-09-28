<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphQL\Client;
use GraphQL\Directive;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use GraphQL\Variable;
use GraphQL\VariableReference;

$client = new Client('https://graphql-pokeapi.graphcdn.app/');

// @include applies to the sprites field. The variable controls it per request.
$gql = (new Query('pokemon'))
    ->setVariables([new Variable('showSprites', 'Boolean', true)])
    ->setArguments(['name' => 'pikachu'])
    ->setSelectionSet([
        'name',
        (new Query('sprites'))
            ->setDirectives([new Directive('include', ['if' => new VariableReference('showSprites')])])
            ->setSelectionSet(['front_default']),
    ]);

foreach ([true, false] as $showSprites) {
    try {
        $results = $client->runQuery($gql, true, ['showSprites' => $showSprites]);
    } catch (QueryError $exception) {
        print_r($exception->getErrors());
        exit(1);
    }

    echo $showSprites ? "With sprites:\n" : "Without sprites:\n";
    print_r($results->getData());
}
