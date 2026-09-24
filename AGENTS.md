# AGENTS.md

Guidance for coding agents working in this repository. `CLAUDE.md` imports this file, so Claude Code, Codex and anything else that reads `AGENTS.md` all get the same instructions — keep this file self-contained and edit it here rather than adding a second copy.

## Project

WordPress plugin (Equalize Digital) for in-post accessibility scanning and WCAG auditing, powered by axe-core with custom rules. Free version on WordPress.org; Pro adds features.

- PHP ≥ 7.4 — no PHP 8-only syntax. WP minimum: see `readme.txt`.
- Text domain: `accessibility-checker`
- Namespaces: PSR-4 `EqualizeDigital\AccessibilityChecker\` → `includes/classes/`, `…\Admin\` → `admin/`. Legacy classmap: `admin/`, `includes/classes/`, `includes/deprecated/`. Main class: `EDAC\Inc\Plugin` (`includes/classes/class-plugin.php`).
- Prefixes: constants `EDAC_`/`EDACP_`; hooks, options, globals `edac_`/`edacp_` (pro). Never add unprefixed ones.
- Pro gating: `edac_is_pro()` — requires `EDACP_VERSION` defined and `EDAC_KEY_VALID` defined + truthy (set from the `edacp_license_status` option).

## Commands

```bash
npm run build | dev              # webpack prod / watch
npm run lint                     # phpcs + eslint + stylelint (all three)
npm run lint:php[:fix]           # phpcs / phpcbf
npm run lint:js[:fix]            # wp-scripts eslint
npm run lint:css[:fix]           # wp-scripts stylelint
npm run phpstan
npm run test:jest
npm run test:php                 # set up docker WP test env (once/refresh)
npm run test:php:run             # run PHPUnit in container
npm run test:php:coverage        # PHPUnit with clover + html coverage
npm run test:php:stop
docker compose exec phpunit vendor/bin/phpunit --filter <name>   # single test
npm run dist                     # distribution zip (swaps composer to no-dev; don't run casually)
composer generate-hooks-docs     # regenerate docs/hooks.md
composer lint                    # parallel-lint syntax check
```

`npm install` runs `postinstall` → `patch-package && ./scripts/prepare.sh`.

## Architecture

- `accessibility-checker.php` — constants, composer autoload, `new Plugin()`, requires legacy procedural files in `includes/` (`helper-functions.php`, `options-page.php`, …). Admin vs frontend split via `is_admin()`. Also defines `edac_register_rules()`.
- `includes/classes/` — `Rules/`, `Fixes/`, `Blocks/`, `Capabilities/`, `Shortcodes/`, `MyDot/`, `SystemInfo/`, `WPCLI/`, plus legacy `class-*.php` at the top level (`class-plugin.php`, `class-rest-api.php`, `class-summary-generator.php`, `class-simplified-summary.php`, `class-admin-toolbar.php`, `class-enqueue-frontend.php`, `class-lazyload-filter.php`, `class-accessibility-statement.php`).
- `admin/` — legacy `class-*.php` = `EDAC\Admin`; PSR-4 CamelCase = `EqualizeDigital\…\Admin\`. Subdirectories: `AdminPage/`, `site-health/`, `opt-in/`, `css/`, `js/`.
- `partials/` — PHP templates.
- `src/` → webpack → `build/`, one bundle per entry: `admin`, `editorApp`, `pageScanner` (scan engine), `frontendHighlighterApp`, `frontendFixes`, `emailOptIn`, `sidebar`, `issueModal`, `sharedComponents`, `srOnlyFormat`, `simplifiedSummaryBlock`. `src/common/` holds shared code but is not itself an entry. `@wordpress/*` packages are externalized in `webpack.config.js`.

### Rules

Rules run client-side via a customized axe-core scan (`src/pageScanner`). Each rule has a **JS rule definition**, one or more **JS checks**, and a **PHP Rule class** carrying the user-facing metadata (title, summary, how-to-fix, WCAG mapping, severity).

- `edac_register_rules()` → `RuleRegistry::load_rules()` (loads everything in `Rules/Rule/`, each a `RuleInterface`), filterable by `edac_filter_register_rules`.
- Fixes register on `plugins_loaded` priority 20; code adding fixes must hook below 20.
- **A JS rule's `id` must equal its PHP rule `slug`, exactly** — otherwise results never map to the metadata. IDs are `snake_case`; filenames are `kebab-case`.

Adding a rule `example_rule` touches five places:

1. `src/pageScanner/rules/example-rule.js` — default-exports an axe rule object: `id`, `selector`, `excludeHidden`, `tags`, `all`/`any`/`none`, `metadata`.
2. `src/pageScanner/checks/example-check.js` — default-exports `{ id, evaluate( node ) }`. `evaluate` runs per matched node on every scan, so keep it fast and hoist constant lookups out of loops.
3. `src/pageScanner/config/rules.js` — import both, add the rule to `rulesArray` and the check to `checksArray`. `customRuleIdsArray` derives from `rulesArray`; stock axe-core rules live in `standardRuleIdsArray` instead.
4. `includes/classes/Rules/Rule/ExampleRule.php` — implements `RuleInterface` with static `get_rule(): array`. Copy an existing rule (e.g. `ImgAltEmptyRule.php`) for the exact shape. Key fields: `slug` (= the JS `id`), `rule_type` (`error`/`warning`), `ruleset => 'js'`, `wcag`, `severity` (1–4, lower = more severe), `affected_disabilities` (constants from `AffectedDisabilities`), plus `title`, `summary`, `summary_plural`, `why_it_matters`, `how_to_fix`, `references`, `info_url`.
5. `includes/classes/Rules/RuleRegistry.php` — add the class to the `$rule_classes` array.

Then: Jest tests in `tests/jest/pageScanner/` (`rules/` and `checks/`, mirroring src) covering both fixtures that should flag *and* ones that should pass — false-positive regressions are the common failure here. `tests/phpunit/RegisterRulesTest.php` validates rule definitions if you touched PHP metadata. `npm run build` before the rule appears on a running site.

Fixable issues are a separate system: `includes/classes/Fixes/` (`FixesManager`, `FixInterface`) plus front-end JS in `src/frontendFixes/`. A rule does not need a fix.

## Standards (enforced — do not deviate)

- PHP: WPCS + WordPress-VIP-Go (`phpcs.xml`). Tabs. Full docblocks. New classes: PSR-4 CamelCase; register hooks in `init()`/`init_hooks()`, never in constructors (legacy violators exist — don't copy, don't refactor unprompted).
- JS: `@wordpress/eslint-plugin`. Tabs. React in `sidebar/`, `issueModal/`, `sharedComponents/`.
- i18n: every user-facing string translatable, `accessibility-checker` domain, escaped on output; `// translators:` comment on every sprintf placeholder. JS: `wp.i18n`.
- UI must meet WCAG 2.1 AA: focus management, keyboard nav, ARIA, semantic HTML.

## Testing & CI (required)

Which checks apply:

| Change | Run |
|---|---|
| JS in `src/` | `npm run test:jest`, `npm run lint:js` |
| CSS/SCSS | `npm run lint:css` |
| PHP anywhere | PHPUnit (docker), `npm run lint:php`, `npm run phpstan` |
| Rule metadata (PHP Rule classes) | PHPUnit `RegisterRulesTest`, plus Jest if the JS side changed |
| Hooks added/renamed | `composer generate-hooks-docs` — CI fails on a stale `docs/hooks.md` |

- PHPUnit runs in docker against the WP test suite: `npm run test:php` once to set up, then `test:php:run`, `test:php:stop`. Tests in `tests/phpunit/`; PSR-4 helpers under `EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\`.
- Jest config at `tests/jest/jest.config.js`. `@wordpress/*` imports need explicit `moduleNameMapper` entries — a missing one surfaces as "Cannot find module '@wordpress/…'", and the mapper regexes must be anchored to avoid subpath mismatches.
- No committed E2E suite.
- Bug fixes need a regression test. Run the checks covering your change before pushing.
- CI mirrors local: `phpunit.yml`, `jest-tests.yml`, `lint-php.yml`, `lint-js.yml`, `cs.yml`, `code-coverage-and-coveralls.yml`, `verify-hooks-docs.yml`, `deploy-on-release-*`. If CI fails where local passed, check the PHP-version matrix and the hooks-docs verifier first.

## Workflow

- Base branch for feature/fix PRs: **`develop`** (not `main`). Releases go through `release/x.y.z` branches.
- Branch names: `<firstname>/<linear-id>-<kebab-slug>` for Linear-tracked work, `<firstname>/no-issue/<slug>` otherwise. Automation uses `automation/…` or `claude/…`.
- Conventional commits (`fix:`, `feat:`, `perf:`, `refactor:`, `chore:`, `docs:`, `test:`). Imperative, lower-case after the prefix, no trailing period.
- PR title when Linear-tracked: `PRO-1234: fix: <description>`. Fill in the checklist in `.github/PULL_REQUEST_TEMPLATE.md` — link the issue and cover the change with tests.
- Wait for CI to go green, CodeRabbit's check included, before treating a PR as review-ready.
- Review comments: reply to each one you act on with the commit hash and a one-line description of the fix. Disagree in a reply with reasoning rather than silently. Never resolve a thread without a reply.
- Commit lock files only when changing dependencies.
