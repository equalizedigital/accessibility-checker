# CLAUDE.md

Guidance for Claude Code in this repository. Workflow details live in `.claude/skills/` (`add-a11y-rule`, `tests-and-lint`, `branch-and-pr`) — prefer those over guessing.

## Project

WordPress plugin (Equalize Digital) for in-post accessibility scanning and WCAG auditing, powered by axe-core with custom rules. Free version on WordPress.org; Pro adds features.

- PHP ≥ 7.4 — no PHP 8-only syntax. WP minimum: see `readme.txt`.
- Text domain: `accessibility-checker`
- Namespaces: PSR-4 `EqualizeDigital\AccessibilityChecker\` → `includes/classes/`, `…\Admin\` → `admin/`. Legacy classmap: `EDAC\Inc` (`includes/classes/`), `EDAC\Admin` (`admin/`), plus `includes/deprecated/`. Main class: `EDAC\Inc\Plugin`.
- Prefixes: constants `EDAC_`/`EDACP_`; hooks, options, globals `edac_`/`edacp_` (pro). Never add unprefixed ones.
- Pro gating: `edac_is_pro()` — requires `EDACP_VERSION` defined and `EDAC_KEY_VALID` defined + truthy (set from the `edacp_license_status` option).

## Commands

```bash
npm run build | dev              # webpack prod / watch
npm run lint:php[:fix]           # phpcs / phpcbf
npm run lint:js[:fix]            # wp-scripts eslint
npm run phpstan
npm run test:jest
npm run test:php                 # setup docker WP test env (once/refresh)
npm run test:php:run             # run PHPUnit in container
docker compose exec phpunit vendor/bin/phpunit --filter <name>   # single test
npm run test:php:stop
npm run dist                     # distribution zip (swaps composer to no-dev; don't run casually)
composer generate-hooks-docs     # regenerate docs/hooks.md
```

## Architecture

- `accessibility-checker.php` — constants, composer autoload, `new Plugin()`, requires legacy procedural files in `includes/` (`helper-functions.php`, `options-page.php`, …). Admin vs frontend split via `is_admin()`.
- `includes/classes/` — `Rules/` (`RuleRegistry` loads all rules from `Rules/Rule/`, each a `RuleInterface`), `Fixes/` (`FixesManager` singleton, `FixInterface`), `MyDot/`, `SystemInfo/`, `WPCLI/`.
- `admin/` — legacy `class-*.php` = `EDAC\Admin`; PSR-4 CamelCase = `EqualizeDigital\…\Admin\`. `AdminPage/`, `site-health/`, `opt-in/`.
- `partials/` — PHP templates.
- `src/` → webpack → `build/`, one bundle per dir: `admin`, `editorApp`, `pageScanner` (scan engine — see `add-a11y-rule` skill), `frontendHighlighterApp`, `frontendFixes`, `emailOptIn`, `sidebar`, `issueModal`, `sharedComponents`, `srOnlyFormat`, `common`. `@wordpress/*` packages are externalized in `webpack.config.js`.

### Rules

- Rules load via `edac_register_rules()` → `RuleRegistry::load_rules()`, filterable by `edac_filter_register_rules`.
- Fixes register on `plugins_loaded` priority 20; code adding fixes must hook below 20.
- A JS rule's `id` must equal its PHP rule `slug`.

## Standards (enforced — do not deviate)

- PHP: WPCS + WordPress-VIP-Go (`phpcs.xml`). Tabs. Full docblocks. New classes: PSR-4 CamelCase; register hooks in `init()`/`init_hooks()`, never in constructors (legacy violators exist — don't copy, don't refactor unprompted).
- JS: `@wordpress/eslint-plugin`. Tabs. React in `sidebar/`, `issueModal/`, `sharedComponents/`.
- i18n: every user-facing string translatable, `accessibility-checker` domain, escaped on output; `// translators:` comment on every sprintf placeholder. JS: `wp.i18n`.
- UI must meet WCAG 2.1 AA: focus management, keyboard nav, ARIA, semantic HTML.

## Testing & CI (required)

- PHPUnit: `tests/phpunit/` (docker, WP test suite). Jest: `tests/jest/` (jsdom; `@wordpress/*` imports need anchored `moduleNameMapper` entries). No committed E2E suite.
- Bug fixes need a regression test. Run the checks covering your change before pushing (`tests-and-lint` skill).
- Hooks added/renamed ⇒ run `composer generate-hooks-docs`; CI fails on stale `docs/hooks.md`.
- CI mirrors local: phpunit (PHP 8.1/8.2), jest, lint-php/js, cs, security, coverage, verify-hooks-docs, deploy-on-release-*.

## Workflow

- Base branch `develop`. Conventional commits (`fix:`, `feat:`, `perf:`, …). Branch/PR conventions: `branch-and-pr` skill.
- Commit lock files only when changing dependencies.
