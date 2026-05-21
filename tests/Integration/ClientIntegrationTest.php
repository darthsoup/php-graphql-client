<?php

namespace GraphQL\Tests\Integration;

use GraphQL\Client;
use GraphQL\Query;
use GraphQL\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class ClientIntegrationTest extends TestCase
{
    private const string ENDPOINT = 'https://graphql-pokeapi.graphcdn.app/';

    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client(self::ENDPOINT);
    }

    #[Test]
    public function testRunRawQuery(): void
    {
        $query = <<<GRAPHQL
        query {
            pokemon(name: "pikachu") {
                id
                name
            }
        }
        GRAPHQL;

        $results = $this->client->runRawQuery($query, true);
        $data = $results->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pokemon', $data);
        $this->assertArrayHasKey('id', $data['pokemon']);
        $this->assertArrayHasKey('name', $data['pokemon']);
        $this->assertSame('pikachu', $data['pokemon']['name']);
    }

    #[Test]
    public function testRunQueryWithQueryObject(): void
    {
        $gql = (new Query('pokemon'))
            ->setArguments(['name' => 'pikachu'])
            ->setSelectionSet([
                'id',
                'name',
                (new Query('sprites'))->setSelectionSet(['front_default']),
            ]);

        $results = $this->client->runQuery($gql, true);
        $data = $results->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pokemon', $data);
        $this->assertSame('pikachu', $data['pokemon']['name']);
        $this->assertArrayHasKey('sprites', $data['pokemon']);
        $this->assertArrayHasKey('front_default', $data['pokemon']['sprites']);
    }

    #[Test]
    public function testRunQueryWithQueryBuilder(): void
    {
        $builder = (new QueryBuilder('pokemon'))
            ->setArgument('name', 'pikachu')
            ->selectField('id')
            ->selectField('name')
            ->selectField(
                (new QueryBuilder('moves'))
                    ->selectField(
                        (new QueryBuilder('move'))
                            ->selectField('name')
                    )
            );

        $results = $this->client->runQuery($builder, true);
        $data = $results->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pokemon', $data);
        $this->assertSame('pikachu', $data['pokemon']['name']);
        $this->assertArrayHasKey('moves', $data['pokemon']);
        $this->assertNotEmpty($data['pokemon']['moves']);
    }
}
