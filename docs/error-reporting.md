# Reporting failures

Use the exception's existing details to select the fields your reporter needs.
`QueryError` provides `getErrors()`, `getData()`, `getResponseData()`, and
`getResponseObject()`; `InvalidResponseException` provides `getResponse()`.
PSR-18 network exceptions provide `getRequest()`. For example, with a PSR-3
logger:

```php
try {
    $results = $client->runQuery($gql);
} catch (\GraphQL\Exception\QueryError $exception) {
    $logger->error('GraphQL query failed', [
        'exception' => $exception,
        'http_status' => $exception->getResponseObject()?->getStatusCode(),
        'error_count' => count($exception->getErrors()),
    ]);
    throw $exception;
} catch (\Throwable $exception) {
    $logger->error('GraphQL request failed', ['exception' => $exception]);
    throw $exception;
}
```

The library does not send events. Avoid attaching variables, headers, query text,
response bodies, or partial data to reports by default. `QueryError::getMessage()`
is supplied by the server and may need scrubbing in the reporter's configuration.
