---
name: add-a11y-rule
description: Add or modify an accessibility rule/check in Accessibility Checker — covers the JS axe-core rule + check, config registration, the PHP Rule class, RuleRegistry, and the tests each piece needs.
---

# Adding or modifying an accessibility rule

Rules run client-side via a customized axe-core scan (`src/pageScanner`). Each rule has a **JS rule definition**, one or more **JS checks**, and a **PHP Rule class** that provides the user-facing metadata (title, summary, how-to-fix, WCAG mapping, severity).

## Files involved for a new rule `example_rule`

1. **JS rule** — `src/pageScanner/rules/example-rule.js`
   Default-exports an axe-core rule object:
   ```js
   export default {
       id: 'example_rule',            // snake_case; must match PHP slug
       selector: '...',               // CSS selector scoping the rule
       excludeHidden: true,
       tags: [ 'cat.…', 'wcag1a', 'wcag111' ],
       all: [], any: [ 'example_check' ], none: [],
       metadata: { description: '…', help: '…' },
   };
   ```
   Rule IDs are `snake_case`; filenames are `kebab-case`.

2. **JS check** — `src/pageScanner/checks/example-check.js`
   Default-exports `{ id: 'example_check', evaluate( node ) { … return true/false; } }`. Keep `evaluate` fast — it runs per matched node on every scan. Hoist constant lookups out of loops (see commit `e770024e` for precedent).

3. **Register both** in `src/pageScanner/config/rules.js`:
   - `import` at the top,
   - add the rule to `rulesArray` and the check to `checksArray`.
   `customRuleIdsArray` is derived automatically from `rulesArray`. Rules that come from stock axe-core instead live in `standardRuleIdsArray`.

4. **PHP Rule class** — `includes/classes/Rules/Rule/ExampleRule.php`
   Implements `RuleInterface` with a static `get_rule(): array`. Copy an existing rule (e.g. `ImgAltEmptyRule.php`) for the exact shape. Key fields: `slug` (must equal the JS rule `id`), `rule_type` (`error`/`warning`), `ruleset => 'js'`, `wcag`, `severity` (1–4, lower = more severe), `affected_disabilities` (constants from `AffectedDisabilities`), plus `title`, `summary`, `summary_plural`, `why_it_matters`, `how_to_fix`, `references`, `info_url` (a11ychecker.com help link). All user-facing strings translatable in the `accessibility-checker` text domain with translator comments on sprintf placeholders.

5. **Register the PHP class** in `includes/classes/Rules/RuleRegistry.php` — add the class name to the `$rule_classes` array (alphabetical-ish order).

## Tests

- **Jest**: `tests/jest/pageScanner/` — rule tests under `rules/`, check tests under `checks/` (mirroring src). Run with `npm run test:jest`. Test both fixtures that should flag and ones that should pass (false-positive regression tests matter a lot here — see PRO-1168 where `.ogg` audio was flagged as video).
- **PHPUnit**: `tests/phpunit/RegisterRulesTest.php` validates registered rule definitions; run the suite if you touched PHP rule metadata.

## Gotchas

- The JS `id` and PHP `slug` must match exactly or results won't map to the rule metadata.
- `npm run build` (webpack) is needed before the rule shows up in a running site; `npm run dev` for watch mode.
- Fixable issues have a separate system: `includes/classes/Fixes/` (`FixesManager`, `FixInterface`) and front-end fix JS in `src/frontendFixes/`. A rule does not need a fix, but if one is requested it's a separate class + registration.
- File-extension checks (video/audio/document links) usually compare against extension lists — check for existing helpers before writing a new list.
