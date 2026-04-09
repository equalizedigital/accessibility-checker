# Project Context

- **Owner:** Steve Jones
- **Project:** accessibility-checker — WordPress plugin by Equalize Digital for in-post accessibility scanning (WCAG 2.1 compliance, axe-core based, no external API required, no per-page fees)
- **Stack:** PHP 7.4+, WordPress, React/JavaScript, Webpack, PHPUnit, Jest, ESLint, PHPCS
- **Namespace:** EqualizeDigital\AccessibilityChecker (new classes), EDAC (legacy classes)
- **Text Domain:** accessibility-checker
- **Min PHP:** 7.4
- **Key patterns:** PSR-4 autoloading, WordPress hooks/filters with `edac_` prefix, WP_Error for PHP errors, wpdb for DB access
- **DB table:** accessibility_checker — has `extra_data` TEXT NULL column for per-issue JSON (e.g., contrast details)
- **Test commands:** `npm run test:php` (PHPUnit via Docker), `npm run test:php:run` (existing container)
- **Lint commands:** `npm run lint:php`, fixer: `npm run lint:php:fix`
- **Build:** `npm run build` (webpack production), `npm run dev` (watch)
- **Created:** 2026-04-09

## Learnings

<!-- Append new learnings below. Each entry is something lasting about the project. -->
