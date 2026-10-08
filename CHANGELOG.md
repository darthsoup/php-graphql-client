# Changelog

## [2.1.0] - 2026-10-08

### Added

- Lazy pagination through `Client::paginate()` for Lighthouse page-based pagination, Lighthouse cursor connections, and offset/count responses, including custom starting positions and nested field paths.
- GraphQL directives, reusable fragment definitions and spreads, input objects, and explicit variable references through `Directive`, `FragmentDefinition`, `FragmentSpread`, `InputObject`, and `VariableReference`.
- Support for the HTTP `QUERY` method alongside `POST`.
- `QueryError::getErrors()`, `getData()`, and `getResponseData()` to inspect all GraphQL errors and partial response data.
- Focused documentation and runnable examples for queries, mutations, directives, pagination, and error reporting.
- Unit coverage for client authentication, pagination configuration, malformed pages, custom starting positions, variable conflicts, and overflow protection.

### Changed

- Queries and mutations share `AbstractOperation`; `Client::runQuery()` accepts either operation and query builders return `AbstractOperation`.
- The default Guzzle transport uses PSR-18 `sendRequest()`. Redirects are not followed, including when `allow_redirects` is configured.
- Response validation reports malformed JSON, invalid response shapes, and non-success HTTP statuses through `InvalidResponseException`. HTTP 400 responses containing GraphQL errors still throw `QueryError`; transport exceptions propagate.
- GraphQL field, alias, operation, argument, variable, and variable-type names are validated, and unsupported input values are rejected.
- Library source files use strict types. PHP 8.3 remains the minimum supported version.
- Formatting checks use PHP CS Fixer with Composer commands `style:check` and `style:fix`.

### Fixed

- String literals escape quotes, backslashes, and control characters correctly.
- Rendering a parent query no longer mutates nested queries.
- Response handling preserves all GraphQL errors, partial data, and nullable data, and validates object/list shapes when formatting results as arrays.
- Pagination rejects conflicting control variables, malformed records and metadata, cursor cycles, empty pages that cannot advance, invalid counts, and integer overflow.

### Compatibility notes

- Replace imports of `GraphQL\Util\StringLiteralFormatter` with `GraphQL\Support\StringLiteralFormatter`. Case conversion is also available through `GraphQL\Support\StringCaseConverter`.
- `GraphQL\Util\GuzzleAdapter` has been removed; pass a PSR-18 client directly to `Client` instead.
- `Mutation` now extends `AbstractOperation` rather than `Query`. Custom operation subclasses and builder overrides should use the shared operation abstraction.
- HTTP error handling now uses `InvalidResponseException` instead of relying on Guzzle HTTP exceptions. Update exception handlers as needed.
- Inputs previously accepted without validation may now throw `InvalidArgumentException` or `JsonException`.

[2.1.0]: https://github.com/darthsoup/php-graphql-client/compare/v2.0.2...v2.1.0
