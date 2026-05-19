<?php

namespace GraphQL\Auth;

use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialProvider;
use Aws\Signature\SignatureV4;
use GraphQL\Exception\AwsRegionNotSetException;
use GraphQL\Exception\MissingAwsSdkPackageException;
use Psr\Http\Message\RequestInterface;

class AwsIamAuth implements AuthInterface
{
    protected const SERVICE_NAME = 'appsync';

    /**
     * @codeCoverageIgnore
     *
     * AwsIamAuth constructor.
     */
    public function __construct()
    {
        if (!class_exists(\Aws\Signature\SignatureV4::class)) {
            throw new MissingAwsSdkPackageException();
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function run(RequestInterface $request, array $options = []): RequestInterface
    {
        $region = $options['aws_region'] ?? null;
        if ($region === null) {
            throw new AwsRegionNotSetException();
        }

        return $this->getSignature($region)->signRequest(
            $request,
            $this->getCredentials(),
            self::SERVICE_NAME
        );
    }

    protected function getSignature(string $region): SignatureV4
    {
        return new SignatureV4(self::SERVICE_NAME, $region);
    }

    protected function getCredentials(): Credentials
    {
        $provider = CredentialProvider::defaultProvider();

        return $provider()->wait();
    }
}
