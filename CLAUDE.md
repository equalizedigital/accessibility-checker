# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Accessibility Checker is a WordPress plugin by Equalize Digital that provides in-post accessibility scanning and WCAG compliance auditing. Powered by axe-core with custom rules. Has a free version on WordPress.org and a Pro version with additional features.

- **PHP minimum**: 7.4
- **WordPress minimum**: 6.2
- **Text domain**: `accessibility-checker`
- **Namespaces**: `EqualizeDigital\AccessibilityChecker` (PSR-4, newer classes) and `EDAC\Inc` (legacy classmap). Both map to `includes/classes/`. The main `Plugin` class is at `EDAC\Inc\Plugin`.
- **Constants prefix**: `EDAC_` (free), `EDACP_` (pro-related, backwards compatibility)
- **Hook/filter prefix**: `edac_`
- **Pro version**: Gated by the `EDAC_KEY_VALID` constant (checks `edacp_license_status` option). Pro-specific options use the `edacp_` prefix.

## Commands

### Building

```bash
npm run build          # Production webpack build
npm run dev            # Development build with watch mode
```

### Linting

```bash
npm run lint           # Run both PHP (phpcs) and JS (eslint) linting
npm run lint:php       # PHP only
npm run lint:js        # JS only
npm run lint:php:fix   # Auto-fix PHP
npm run lint:js:fix    # Auto-fix JS
npm run phpstan        # PHP static analysis
```

### Testing

```bash
# PHP tests (Docker-based)
npm run test:php              # Setup Docker environment + run tests
npm run test:php:run          # Run tests in already-running container
npm run test:php:stop         # Stop Docker containers

# Run a single PHP test file (container must be running):
docker compose exec phpunit vendor/bin/phpunit ./tests/phpunit/path/to/TestFile.php

# Run tests matching a filter:
docker compose exec phpunit vendor/bin/phpunit --filter test_method_name

# PHP tests without Docker (if WP test suite is available locally):
composer test

# PHP test coverage (container must be running):
npm run test:php:coverage

# JavaScript tests
npm run test:jest
```

### Distribution

```bash
npm run dist                    # Build production + create .zip for distribution
npm run dist:keep-build-folder  # Same but preserves the build folder
npm run dist:dotorg             # Alias for dist:keep-build-folder (WordPress.org releases)
```

## Architecture

### Plugin Bootstrap

`accessibility-checker.php` defines constants (`EDAC_VERSION`, `EDAC_DB_VERSION`, `EDAC_PLUGIN_DIR`, `EDAC_KEY_VALID`, etc.), loads Composer autoload, instantiates `EDAC\Inc\Plugin`, and requires legacy procedural files. The Plugin class separates admin vs frontend initialization via `is_admin()`.

### PHP Structure

- **`includes/classes/`** — Houses most of the plugin's classes. Uses a mixed autoloading strategy: newer classes follow PSR-4 under `EqualizeDigital\AccessibilityChecker\`, while legacy core classes use classmap under `EDAC\Inc`. Both are configured in `composer.json` and map to the same directory.
  - `class-plugin.php` — Main bootstrap (`EDAC\Inc\Plugin`), registers hooks, loads components
  - `Rules/` — Accessibility rule system: `RuleRegistry` loads ~43 rules from `Rules/Rule/`, each implements `RuleInterface`
  - `Fixes/` — Fix system: `FixesManager` (singleton) manages ~15 fixes from `Fixes/Fix/`, each implements `FixInterface`
  - `Admin/` — Contains `Updates/` subnamespace
  - `Tokens/` — Token handling infrastructure
  - `WPCLI/` — WP-CLI commands via `BootstrapCLI.php` and `Command/` subdirectory (`CleanupOrphanedIssues`, `DeleteStats`, `GetSiteStats`, `GetStats`)
- **`admin/`** — Admin-area classes under `EqualizeDigital\AccessibilityChecker\Admin\`. Mixed naming: legacy files use `class-*.php` (WordPress style), newer use PSR-4 CamelCase.
  - `AdminPage/` — Fixes settings page with `FixesSettingType/` system (Checkbox, Text)
  - `site-health/` — WordPress Site Health integration (free/pro checks, audit history, information)
  - `opt-in/` — Email opt-in system
- **`partials/`** — PHP template files for admin pages, meta boxes, settings
- **`includes/`** — Legacy procedural code: `activation.php`, `deactivation.php`, `helper-functions.php`, `options-page.php`

### JavaScript / React Structure

Webpack bundles from `src/` into `build/`. Each entry point is a separate bundle:

- **`src/sidebar/`** — Gutenberg sidebar panel (React, uses `@wordpress/data` store)
- **`src/issueModal/`** — Issue details modal (React)
- **`src/admin/`** — Admin page JavaScript + SCSS
- **`src/editorApp/`** — Block editor integration
- **`src/frontendHighlighterApp/`** — Frontend issue highlighting overlay
- **`src/pageScanner/`** — Page scanning engine
- **`src/frontendFixes/`** — Client-side accessibility fixes
- **`src/emailOptIn/`** — Email opt-in modal and styles
- **`src/common/`** — Shared utilities

WordPress packages (`@wordpress/i18n`, `@wordpress/element`, `@wordpress/data`, etc.) are externalized — they come from the WP runtime, not bundled. Only `@wordpress/i18n` is listed in webpack externals.

### Key Patterns

**Hook registration**: Classes have `init()` or `init_hooks()` methods. Never add actions/filters in constructors — always in init methods.

```php
public function __construct() {
    // No hooks here
}

public function init() {
    add_action( 'hook_name', [ $this, 'method' ] );
}
```

**Rule/Fix registration**: Rules loaded via `RuleRegistry::load_rules()`, filterable via `edac_filter_register_rules`. Fixes registered via `FixesManager::register_fixes()`. Both fire on `plugins_loaded` at priority 20.

**Naming conventions**:
- New PHP classes: `ClassName.php` (CamelCase)
- Legacy PHP classes: `class-class-name.php` (WordPress style)
- JS components: `ComponentName.js`
- Global functions: `edac_` prefix
- Custom hooks: `edac_` prefix

### Testing Infrastructure

- **PHPUnit**: Tests in `tests/phpunit/`, config in `phpunit.xml.dist`, bootstrap in `tests/bootstrap.php`. Runs in Docker (MySQL 5.7 + WordPress test suite).
- **Jest**: Tests in `tests/jest/`, config in `tests/jest/jest.config.js`, jsdom environment.
- **E2E**: Cypress tests in `tests/e2e/`.

### Docker Test Environment

`docker-compose.yml` provides:
- `db-phpunit` — MySQL 5.7 container
- `phpunit` — WordPress + PHPUnit container (mounts plugin at `/var/www/html/wp-content/plugins/accessibility-checker`)

## Coding Standards

- **PHP**: WordPress Coding Standards (WPCS) + WordPress-VIP-Go, enforced by phpcs.xml. PSR-4 autoloading for new classes.
- **JS**: WordPress ESLint preset (`@wordpress/eslint-plugin/esnext`). React rules apply to `src/sidebar/` and `src/issueModal/`.
- **Indentation**: Tabs (per WordPress standards and .editorconfig).
- **i18n**: All user-facing strings must use `accessibility-checker` text domain. JS uses `wp.i18n` functions (`__`, `_n`, `_x`, `_nx`).
- **Pre-commit**: Husky runs lint-staged (phpcs on PHP files) before commits.

## CI/CD (GitHub Actions)

Workflows in `.github/workflows/`:

- **Testing**: `phpunit.yml` (PHP 8.1/8.2, MySQL 8.0), `jest-tests.yml`
- **Code quality**: `cs.yml` (code style), `lint-php.yml`, `lint-js.yml`, `security.yml`
- **Coverage**: `code-coverage-and-coveralls.yml`
- **Deployment**: `deploy-on-release-to-dot-org.yml` (WordPress.org), `deploy-on-release-to-instawp.yml`, `deploy-on-release-to-woocommerce.yml`
- **Utilities**: `make-pot.yml` (translations), `verify-hooks-docs.yml`, `backport-to-develop.yml`, `wp-version-checker.yml`, `build-plugin-with-ref.yml`

## Workflow Notes

- Commit lock files only when adding/updating packages.
- Lint and test before committing: `npm run lint && npm run test:jest`.
- UI code in this plugin must follow WCAG 2.1 AA — proper focus management, keyboard navigation, ARIA attributes, semantic HTML.
