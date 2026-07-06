---
name: tests-and-lint
description: How to run and interpret the Accessibility Checker test/lint stack — Jest, docker-based PHPUnit, PHPCS/PHPCBF, PHPStan, ESLint — and which to run for a given change.
---

# Tests and linting in accessibility-checker

## Which checks apply to a change

| Change | Run |
|---|---|
| JS in `src/` | `npm run test:jest`, `npm run lint:js` |
| PHP anywhere | PHPUnit (docker, below), `npm run lint:php`, `npm run phpstan` |
| Rule metadata (PHP Rule classes) | PHPUnit `RegisterRulesTest`, plus Jest if JS side touched |
| Hooks added/renamed (`edac_`/`edacp_` prefix) | `composer generate-hooks-docs` — CI (`verify-hooks-docs.yml`) fails if `docs/hooks.md` is stale |

## JS

- **Jest**: `npm run test:jest` (config at `tests/jest/jest.config.js`, `--passWithNoTests`). Single file: `npx jest --config=./tests/jest/jest.config.js path/to/test`.
  - `@wordpress/*` packages need explicit `moduleNameMapper` entries in the jest config; a missing one shows up as "Cannot find module '@wordpress/…'". Mapper regexes must be anchored to avoid subpath mismatches (see commit `a4059479`).
- **ESLint**: `npm run lint:js` / `npm run lint:js:fix` (wp-scripts, @wordpress/eslint-plugin).

## PHP

PHPUnit runs inside docker (WordPress test suite, MySQL):

```bash
npm run test:php        # one-time/refresh: starts containers, installs WP test env (scripts/setup-phpunit.sh)
npm run test:php:run    # run the suite: docker compose exec phpunit vendor/bin/phpunit
npm run test:php:stop   # stop containers
```

Filter a single test: `docker compose exec phpunit vendor/bin/phpunit --filter TestName`.
Tests live in `tests/phpunit/`; PSR-4 test helpers under `EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\`.

- **PHPCS**: `npm run lint:php` (WPCS 3 + VIPCS, config `phpcs.xml`); autofix with `npm run lint:php:fix`. Also runs on staged files via husky/lint-staged pre-commit.
- **PHPStan**: `npm run phpstan` (config `phpstan.neon`, szepeviktor/phpstan-wordpress).
- Parallel lint (syntax): `composer lint`.

## Build

`npm run build` (webpack) compiles `src/` to `build/`. `npm run dev` for watch. Distribution zip: `npm run dist` (don't run casually — it swaps composer to no-dev and back).

## CI parity

GitHub workflows mirror the above: `phpunit.yml`, `jest-tests.yml`, `lint-php.yml`, `lint-js.yml`, `cs.yml`, `code-coverage-and-coveralls.yml`, `verify-hooks-docs.yml`. If CI fails on something local passed, check PHP-version matrix and the hooks-docs verifier first.
