<?php

namespace GraphQL\Tests\Auth;

use GraphQL\Auth\AwsIamAuth;
use GraphQL\Exception\AwsRegionNotSetException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AwsIamAuthTest extends TestCase
{
    protected AwsIamAuth $auth;

    protected function setUp(): void
    {
        $this->auth = new AwsIamAuth();
    }

    #[Test]
    public function testRunMissingRegion()
    {
        $this->expectException(AwsRegionNotSetException::class);
        $request = new Request('POST', '');
        $this->auth->run($request, []);
    }

    #[Test]
    public function testRunSuccess(): never
    {
        $this->markTestIncomplete('AWS skip');

        $request = $this->auth->run(
            new Request('POST', ''),
            ['aws_region' => 'us-east-1']
        );
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('X-Amz-Date', $headers);
        $this->assertArrayHasKey('X-Amz-Security-Token', $headers);
        $this->assertArrayHasKey('Authorization', $headers);
    }
}
