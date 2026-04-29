# Project Context

- **Owner:** Steve Jones
- **Project:** accessibility-checker — WordPress plugin by Equalize Digital for in-post accessibility scanning (WCAG 2.1 compliance, axe-core based, no external API required, no per-page fees)
- **Stack:** PHP 7.4+, WordPress, React/JavaScript, Webpack, PHPUnit, Jest, ESLint, PHPCS
- **PHP tests:** `tests/phpunit/` — run with `npm run test:php` (Docker), or `npm run test:php:run` (existing container)
- **Single file:** `docker-compose exec phpunit ./vendor/bin/phpunit ./tests/phpunit/path/to/TestFile.php`
- **Coverage:** `npm run test:php:coverage`
- **JS tests:** `tests/jest/` — run with `npm run test:jest`
- **Test frameworks:** PHPUnit (WordPress testing framework), Jest
- **Created:** 2026-04-09

## Learnings

<!-- Append new learnings below. Each entry is something lasting about the project. -->
