<?php

namespace GraphQL\Tests;

use GraphQL\Exception\QueryError;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueryErrorTest extends TestCase
{
    #[Test]
    public function testConstructQueryError()
    {
        $exceptionMessage = 'some syntax error';
        $errorData = [
            'errors' => [
                [
                    'message' => $exceptionMessage,
                    'location' => [
                        [
                            'line' => 1,
                            'column' => 3,
                        ]
                    ],
                ]
            ]
        ];

        $queryError = new QueryError($errorData);
        $this->assertEquals($exceptionMessage, $queryError->getMessage());
        $this->assertEquals(
            [
                'message' => 'some syntax error',
                'location' => [
                    [
                        'line' => 1,
                        'column' => 3,
                    ]
                ]
            ],
            $queryError->getErrorDetails()
        );
    }
}
