# Repository Guidelines

## Project Structure & Module Organization

This is a PHP 8.3+ GraphQL client distributed through Composer. Public classes live under the `GraphQL\` namespace in `src/`; related implementations are in `src/Auth/`, `src/Exception/`, `src/QueryBuilder/`, and `src/Util/`. Keep existing public class names and imports stable when changing internals. Unit tests are in `tests/Unit/`, live API tests in `tests/Integration/`, runnable usage samples in `examples/`, and focused guides in `docs/`. Keep `README.md` short and link to detailed documentation.

## Build, Test, and Development Commands

- `composer install` installs dependencies and generates the autoloader.
- `composer test` runs the PHPUnit unit suite.
- `composer test:integration` runs tests against the external Pokémon GraphQL API; it requires network access and is not part of routine CI.
- `composer phpstan` checks `src/` at PHPStan level 8.
- `composer style:check` checks PSR-12 formatting; `composer style:fix` applies it.
- `php examples/lighthouse_pagination_example.php` runs an offline usage example.

There is no compilation step. Run the unit tests, PHPStan, and style check before opening a pull request.

## Coding Style & Naming Conventions

Use four spaces, LF line endings, a final newline, and no trailing whitespace, as defined in `.editorconfig`. Follow PSR-12 and use `declare(strict_types=1);` in new PHP files. Name classes and files in PascalCase, methods in camelCase, and test files after the class or behavior they cover (for example, `ClientTest.php`). Keep examples readable and compatible with the Composer autoloader.

## Testing Guidelines

Use PHPUnit 12 and add or adjust unit tests for each behavior change. Prefer mocked HTTP responses in client tests; reserve the integration suite for behavior that genuinely needs the live endpoint. Use `#[Test]` and relevant coverage metadata. Cover success, malformed responses, and error propagation where applicable. PHPUnit treats warnings, risky tests, and deprecations as failures; it also requires coverage metadata.

## Commit & Pull Request Guidelines

Recent commits use short, imperative subjects, sometimes with a prefix such as `feat:` or `chore:`. Write a focused subject describing the change, for example, `feat: support cursor pagination`. In pull requests, explain the behavior and compatibility impact, link a relevant issue when one exists, and list the checks run. Update `docs/` and `examples/` when a public API or usage pattern changes. Add screenshots only for visual changes.
