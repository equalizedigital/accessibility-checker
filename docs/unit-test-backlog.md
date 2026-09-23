# Unit test backlog

Living checklist for the "one small test a day" coverage push. Pick the next unticked
item, add a test for it, and tick it off in the same PR.

## Why

Line coverage (Coveralls) sat at **62.89%** on 2026-09-23 — 6,544 of 10,405 relevant
lines, 3,861 missed. It has been flat (`coverage_change: 0.0`) despite agents adding
tests for *new* code, because new tests for new code only hold the line; they don't
lift it.

**Goal: 80% by end of year** → 8,324 covered lines → **+1,780 lines**, i.e. roughly
**~25 newly-covered lines per working day**.

## How to use this list

- Take the next unticked unit. Keep each PR to a **single unit** (one function, or one
  small class/method) and a **single test file** so review is trivial.
- Prefer *small* units, but an occasional *medium* unit (one class) is fine — purely
  tiny tests average ~10-20 lines each and won't reach the target.
- Branch `no-issue/unit-test-<slug>`, conventional commit `test: cover <unit>`, PR to
  `develop`. Tick the box here in the same PR.
- Follow the existing style (`tests/phpunit/helper-functions/FormatDatetimeFromUtcTest.php`
  is a good template): `SomethingTest extends WP_UnitTestCase`, `test_*` snake_case,
  `@covers`, data providers.
- **Already covered — do not spend time here:** the 44 `Rules/Rule/*` classes
  (`edac_register_rules()` → `RuleRegistry::load_rules()` already calls `get_rule()`),
  `class-rest-api.php`, `MyDot/Connector.php`, `Fixes/**`, `Capabilities/**`,
  `Summary_Generator`. `includes/deprecated/*`, `uninstall.php` and partials are low value.

## Tier 1 — pure/small, no mocking

- [ ] `edac_sanitize_scan_speed` (`includes/options-page.php:1132`)
- [ ] `edac_sanitize_simplified_summary_position` (`includes/options-page.php:1202`)
- [ ] `edac_sanitize_frontend_highlighter_position` (`includes/options-page.php:1215`)
- [ ] `edac_sanitize_simplified_summary_prompt` (`includes/options-page.php:1255`)
- [ ] `edac_sanitize_post_types` (`includes/options-page.php:1320`)
- [ ] `edac_sanitize_accessibility_policy_page` (`includes/options-page.php:1422`)
- [ ] `edac_sanitize_pro_scan_speed` (`includes/options-page.php:1478`)
- [ ] `edac_sanitize_pro_checkbox` (`includes/options-page.php:1492`)
- [ ] `edac_sanitize_pro_archive_scanning` (`includes/options-page.php:1515`)
- [ ] `edac_sanitize_pro_taxonomy_terms` (`includes/options-page.php:1525`)
- [ ] `edac_sanitize_pro_summary_heading` (`includes/options-page.php:1535`)
- [ ] `edac_sanitize_checkbox` — extend `tests/phpunit/helper-functions/SanitizeCheckboxTest.php`
- [ ] `AffectedDisabilities::get_label()` → `tests/phpunit/includes/classes/Rules/AffectedDisabilitiesTest.php`
- [ ] `edac_get_landmark_types` (`includes/helper-functions.php:869`)
- [ ] `edac_get_landmark_filter_options` (`includes/helper-functions.php:905`)
- [ ] `edac_generate_summary_stat` (`includes/helper-functions.php:607`)
- [ ] `edac_link_wrapper` (`includes/helper-functions.php:692`)
- [ ] `edac_is_pro` (`includes/helper-functions.php:1037`)
- [ ] `edac_is_woocommerce_enabled` (`includes/helper-functions.php:726`)
- [ ] `edac_check_if_post_id_is_woocommerce_checkout_page` (`includes/helper-functions.php:736`)
- [ ] `edac_floor_requirement_label` (`includes/options-page.php:252`)
- [ ] `edac_capability_is_editable` (`includes/options-page.php:201`)
- [ ] `edac_role_meets_floor` (`includes/options-page.php:236`)
- [ ] `FixesSettingType\Text::sanitize_text` (`admin/AdminPage/FixesSettingType/Text.php`)
- [ ] `FixesSettingType\Checkbox::sanitize_checkbox` (`admin/AdminPage/FixesSettingType/Checkbox.php`)
- [ ] `Settings::get_scannable_post_statuses()` (`admin/class-settings.php`)
- [ ] `Post_Save::delete_issue_data_on_post_trashing()` (`admin/class-post-save.php`)

## Tier 2 — medium, one unit per PR (light mocking)

- [ ] `Issues_Query::get_sql()` / `get_query()` (`admin/class-issues-query.php`)
- [ ] `site-health\Free` (`admin/site-health/class-free.php`)
- [ ] `site-health\Pro` (`admin/site-health/class-pro.php`)
- [ ] `site-health\Information` (`admin/site-health/class-information.php`)
- [ ] `site-health\Audit_History` (`admin/site-health/class-audit-history.php`)
- [ ] `Admin::add_ref_param_to_links()` (`admin/class-admin.php`)
- [ ] `edac_deactivation()` (`includes/deactivation.php`)
- [ ] `edac_get_simplified_summary()` (`includes/helper-functions.php:458`)
- [ ] `Update_Database::migrate_license_key_to_shared_option()` (reflection, `admin/class-update-database.php`)
- [ ] `Update_Database::migrate_to_selector_based_unique_id()` (reflection, `admin/class-update-database.php`)

## Tier 3 — larger behaviour, one per couple of days (still single-file PRs)

- [ ] `class-scans-stats.php` — split by method
- [ ] `class-welcome-page.php` — split by method
- [ ] `class-ignore-ui.php`
- [ ] `class-frontend-highlight.php` — untested branches
- [ ] `class-orphaned-issues-cleanup.php`
- [ ] `class-ajax.php` — one AJAX handler at a time (`WP_Ajax_UnitTestCase`, see `tests/phpunit/Admin/AjaxReadabilityTest.php`)
