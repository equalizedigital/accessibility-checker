# Capability / Permissions system — session handoff (2026-08-13 EOD)

Fresh session tomorrow. This is where things stand and what's next. Everything below is **committed and pushed** unless noted.

## Branches (all pushed, in sync)
- **free** `accessibility-checker` → `william/pro-1150-audit-history-export-capabilities`
- **pro** `accessibility-checker-pro` → `william/pro-1239-enforce-ignoredismiss-permission-with-a-real-capability`
- audit / export → `william/pro-1150-...` (no new changes this session)
- Steve is actively pushing UI/a11y polish to the **free** branch (permissions.css / permissions.js / PermissionsPage). Each time we push we've had to `git fetch` + rebase our commits on top. Do that first tomorrow before any free push. Only overlap risk is `admin/AdminPage/PermissionsPage.php` and `includes/options-page.php`; rebases have been clean so far.

## What shipped this session (in order)
1. **Removed the per-user grant subsystem** from `SyncCapability` (never shipped, no UI). Engine is role-map-only now.
2. **PRO-1286 uninstall**: free `uninstall.php` now deletes `edacp_ignore_user_roles`; Gap 2 (per-user sweep) **descoped** — no code path grants caps to users anymore (commented on the Linear card, left In Progress).
3. **Fixed the free/pro settings option-key mismatch**: the scanner reads `edacp_scan_all_taxonomies` (pro's key) but free's Taxonomy field wrote a dead `edacp_scan_all_taxonomy_terms`. Free repointed to `edacp_scan_all_taxonomies` (data-preserving). Archive was already aligned.
4. **Removed all of pro's redundant settings UI** (`edacp_register_setting()` + field callbacks + the admin_init hook). Kept `edacp_simplified_summary_heading()` (filter cb) and `edacp_sanitize_checkbox()` (used by ImportExportSettings). The old "Ignore Permissions" field was a live duplicate of the free Permissions tab.
5. **Activation-seeding fix (the big one)** — root-caused a LIVE bug: `edac_activation()` seeded `edacp_ignore_user_roles = ['administrator']`, so every fresh install looked like a *migrating* site and NEVER got the editor/author/contributor default suite. Fix:
   - Removed the legacy `add_option` from **both** free and pro activation.
   - Seeding moved to **activation** (`edac_seed_capability_defaults_on_install()`), which fires only on a genuine first install (detected via absence of prior `edac_activation_date`). It seeds defaults, marks all caps seeded, stamps the migration satisfied, and syncs.
   - **Removed the init-time seeder** entirely (`edac_seed_default_capabilities()` gone) — dissolves the fragile "is the legacy option empty" detection, the #8 two-writer race, and #10 (empty-legacy site mis-seeded).
   - Entry point now separates paths: fresh install → activation; upgrade → `reconcile()` migration (doesn't fire on plugin update). Verified all 3 paths against wp-env.
6. **Import/export safety + sync trigger** (today's last piece):
   - `edac_sanitize_capability_role_map()` — shared floor+editable-role validator; `handle_save()` delegates to it; wired as `sanitize_option_edac_capability_role_map` filter so the pro importer validates the map. A hand-edited import can't grant a floored cap to an unqualified role.
   - `edac_sync_capability_roles()` — public trigger that applies the stored map onto the current site's roles (needed because `reconcile()` only re-syncs on bundle/version change, not a role-map change).
   - `edac_capability_role_map` added to pro's import/export set via `edacp_import_export_option_names`.
   - Pro `ImportExportSettings::handle_import()` calls `edac_sync_capability_roles()` after a settings import.
   - Scan settings already travel via the registered-settings group (incl. the repointed `edacp_scan_all_taxonomies`).

## Tests
- PHPUnit capability suite green (35 in the capability files; full free suite 923 pass, **1 pre-existing failure**: `AjaxReadabilityTest` hits wordpress.org for `wp_version_check` — network flake in the sandbox, NOT our code).
- Local e2e (`tests/e2e/`, **local-only, never committed**): spec 10 rewritten for the activation architecture (fresh + migrating), spec 09 (permissions gate incl. forced floor-violation) passes. Run: `npx playwright test specs/09-...` etc. wp-env tests instance is up on :8889.
- Test env quirks: phpunit runs via `docker compose exec -T phpunit vendor/bin/phpunit` (that container has NO wp-cli). For live wp-env checks use `npx wp-env run tests-cli wp eval-file <path-relative-to-project>`. Pro's husky hooks are non-executable, so **phpcs does NOT auto-run on pro commits — run `vendor/bin/phpcs <file>` manually before committing pro**.

## STILL TODO (from the Sonnet+Opus dual review — see memory `capability-system-dual-review-findings`)
Priority order:
1. **#4 (HIGH) — grant screen inherits a filterable cap.** `PermissionsPage::handle_save()` is gated only by `edac_filter_settings_capability` (default manage_options but filterable). Add a hard `manage_options` check on the grant action itself. Cheap, clearly right.
2. **#1 — global-dismiss fallback fails OPEN.** Pro `edacp_user_can_ignore_globally()` middle compat tier returns `edac_user_can_ignore()` → any per-post dismisser could pass. Make it fail closed like `edacp_user_can_run_full_site_scan()` (hard edit_others_posts floor).
3. **#5 floors — DECIDED, not yet implemented.** Floor `edac_export_data`, `edac_view_audit_history`, `edac_issues_explorer_access` all at `edit_posts` (in their `edac_capabilities` filter registrations: export in `accessibility-checker-export.php`, audit in `accessibility-checker-audit-history.php`, explorer in `accessibility-checker-pro.php`). Residual accepted: an Author can still export other authors' drafts (edit_posts doesn't gate cross-author). NOTE: once floored, the import sanitizer will also start dropping subscriber for these (already correct behavior).
4. **Doc/comment drift**: #6 (admin-bypass only covers current bundle — fix or correct `docs/capabilities.md:44`), #7 (stale "revokes on deactivation" comments in the 3 add-on `edac_capabilities` registrations), #14 (broken table row for the highlighter helper in `docs/capabilities.md`). Also: `docs/capabilities.md` still describes the OLD init-seeder / "legacy-pending bail" — needs updating to the activation-seeding architecture.
5. **Multisite import** (`accessibility-checker-multisite` → `CloneSettingsRoute::get_blog_settings()`): hardcoded settings list — MISSING `edac_capability_role_map` AND the scan settings `edacp_scan_all_taxonomies`/`edacp_enable_archive_scanning`, and still clones the dead `edacp_ignore_user_roles`. Needs: add the role map (goes through `update_option` inside `switch_to_blog`, then call `edac_sync_capability_roles()` per target site), add the scan settings, drop the legacy option. Same floor-validation applies automatically via the sanitize_option filter IF cloned through update_option (verify).
6. **Simplifications** (low risk, do last): delete `SyncCapability::user_can()/permission_callback()` (dup of `CapabilityChecker`, no non-test callers); static-cache `edac_capability_metadata()`; the dead per-post fallback loops behind `if (!$can_dismiss_globally)`.
7. Verify Sonnet C1: pro `EDACP_ISSUES_EXPLORER_MIN_FREE_VERSION = '1.48.0'` vs free `EDAC_CAPABILITY_MIGRATION_VERSION = '1.49.0'` — confirm the paired release versions before shipping.

## Key architecture notes (so the fresh session doesn't re-derive)
- **Seeding = activation only** (fresh installs). **Migration = `reconcile()` on init** (upgrades; doesn't fire on plugin update). The migration-version stamp (`edac_capability_migration_version_edac_capability_role_map`) is the fresh-vs-migrating signal now — NOT the legacy option's emptiness.
- **Applying a changed role map** requires either the `update_option_edac_capability_role_map` hook (fires on live save) or an explicit `edac_sync_capability_roles()`. `reconcile()` will NOT pick up a role-map-only change (it short-circuits unless bundle/version changed).
- **Floors** are enforced at 3 layers (UI disabled, save handler via the shared sanitizer, sync engine `role_allowed`). The import path now uses the same sanitizer.
- Engine is `SyncCapability` (role-map-only). Metadata/registry in `edac_capability_metadata()`; add-ons contribute via `edac_capabilities` filter.
