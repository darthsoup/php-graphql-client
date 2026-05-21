<?php

namespace GraphQL\Exception;

use RuntimeException;

class MissingAwsSdkPackageException extends RuntimeException
{
    /** @codeCoverageIgnore */
    public function __construct()
    {
        parent::__construct('To be able to use AWS IAM authorization you should install "aws/aws-sdk-php" as a project dependency.');
    }
}
