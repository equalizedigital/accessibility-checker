# Capability Reference

The Accessibility Checker plugin family gates every non-public feature behind either a stock WordPress
capability (`manage_options`, `edit_posts`, `edit_others_posts`, …) or one of the custom `edac_*`
capabilities documented below. The custom capabilities are managed from a single **Permissions** tab
under **Accessibility Checker → Settings**, which presents a per-role matrix rather than one settings
field per feature.

Each add-on (Pro, Export, Audit History) contributes its own capabilities to the same registry, so the
matrix grows and shrinks as add-ons are activated. There is one control surface for the whole family.

## The capability registry

Every capability is declared through the `edac_capabilities` filter. The free plugin assembles the
registry on `plugins_loaded` from that filter (see `edac_capability_metadata()` in
`includes/options-page.php`); the sorted, de-duplicated list of slugs is the **bundle**
(`edac_capability_bundle()`) that the sync engine grants and revokes.

Each registry entry is keyed by capability slug and carries this metadata:

| Key | Meaning |
| --- | --- |
| `label` | Human-readable name shown in the Permissions matrix |
| `description` | One-line explanation shown under the checkbox |
| `group` | Which plugin the capability belongs to; groups the matrix into one bordered card per plugin |
| `owner` | The owning plugin slug |
| `pro` | Whether the capability comes from a paid add-on |
| `floor` | The WordPress capability a role must already have before this capability can be granted to it (see [Floors](#floors)); `''` means no floor |
| `default_roles` | Roles seeded with this capability on first activation (subject to the floor) |

### Registered capabilities

| Capability | Gates | Owner | Floor | Default roles |
| --- | --- | --- | --- | --- |
| `edac_dismiss_own_issues` | Dismiss/reopen accessibility issues on posts the user can edit (authors → their own; editors → any) | accessibility-checker (free) | `edit_posts` | editor, author, contributor |
| `edac_dismiss_issues` | Dismiss/reopen accessibility issues on **any** post, regardless of ownership — a superset of "dismiss own" and a deliberate opt-in overstep of core `edit_post` | accessibility-checker (free) | `edit_posts` | *(none)* |
| `edac_view_frontend_highlighter` | The front-end accessibility highlighter on published content | accessibility-checker (free) | *(none)* | editor, author |
| `edac_dismiss_issues_globally` | "Dismiss Globally" — suppresses an issue across every post sharing its rule + element, not just the one being viewed | accessibility-checker-pro | `edit_others_posts` | *(none)* |
| `edac_issues_explorer_access` | Opening and using the Issues Explorer app | accessibility-checker-pro | *(none)* | *(none)* |
| `edac_full_site_scan` | Running / viewing the full-site scan (**in addition to** `edit_others_posts`, see below) | accessibility-checker-pro | `edit_others_posts` | *(none)* |
| `edac_view_audit_history` | The Audit History admin page and its REST data route (incl. CSV export) | accessibility-checker-audit-history | *(none)* | *(none)* |
| `edac_export_data` | The Export Data admin page and all of its export actions (Issues, Scan Stats, Global Ignores, Audit History) | accessibility-checker-export | *(none)* | *(none)* |

Administrators (`manage_options`) always pass every `edac_*` check, whether or not their role is listed
in the matrix. This is enforced centrally through `map_meta_cap`, so it applies uniformly to every
capability without each feature having to special-case admins.

## Floors

A **floor** is a stock WordPress capability a role must *already* hold before one of these custom
capabilities may be granted to it. Floors exist so a permission can never be handed to a role that
lacks the underlying trust the feature assumes — for example, "Dismiss issues globally" only makes
sense for someone who can already edit other people's posts, so its floor is `edit_others_posts`.

Floors are evaluated against a role's **live** capability set (`edac_role_meets_floor()` in
`includes/options-page.php`), not a hard-coded assumption about what a stock role can do — so a role
customized by another plugin is judged on its actual capabilities. An empty floor is always met.

Floors are enforced at **three layers** so a stale or hand-edited option can never leak a grant:

1. **The Permissions UI** renders a floor-ineligible checkbox as disabled, with a grey-italic reason
   (`edac_floor_requirement_label()`).
2. **The save handler** (`admin-post.php?action=edac_save_permissions`) re-validates every submitted
   grant against the floor and drops any that fail.
3. **The sync engine** (`SyncCapability`) takes a floor-policy callback (the free plugin supplies one
   from the registry metadata). Both `sync_matrix()` and the legacy-migration seed refuse to grant a
   capability to a role that fails its floor, even if the stored role map or a legacy option says
   otherwise.

## The Permissions UI

The Permissions tab (`admin/AdminPage/PermissionsPage.php` +
`partials/admin-page/permissions-page.php`) is a **role picker → per-capability checkboxes** surface,
in the style of User Role Manager. Choosing a role reveals that role's capabilities, grouped into one
bordered card per owning plugin. Floor-ineligible capabilities are shown disabled with their reason.

- Administrators are **excluded** from the role dropdown (`edac_assignable_roles()` drops roles with
  `manage_options`) because they bypass every check anyway.
- A hidden store (`#edac-perm-store`, no-JS safe) is the source of truth; saving posts to
  `admin-post.php` (`edac_save_permissions`), which re-validates floors.
- There is **no per-user grants UI** — see [Granting to one specific user](#granting-a-capability-to-one-specific-user).

## Sync engine, defaults, and migration

The engine is `SyncCapability` (`includes/classes/Capabilities/SyncCapability.php`), constructed by
`edac_ignore_capability()` on `plugins_loaded`. It reads two options:

- `edac_capability_role_map` — `[ capability => [ role, … ] ]`, the role matrix.
- `edac_capability_user_grants` — `[ capability => [ user_id, … ] ]`, snapshot-diffed so unchecking a
  user revokes only the direct grant the engine itself applied.

**Defaults.** On first activation, `edac_seed_default_capabilities()` (`init`, priority 5) seeds each
capability's `default_roles` once — tracked in the `edac_capability_defaults_seeded` option — filtered
by the floor, and respecting any capability an admin has already unchecked. Real sites migrating from
the legacy setting are skipped (their existing configuration wins).

**Migration.** A version-gated migration (`EDAC_CAPABILITY_MIGRATION_VERSION`, currently `1.49.0`)
converts the legacy `edacp_ignore_user_roles` "Ignore Permissions" option into the new role map. It
seeds each legacy-checked role **only** the capabilities that setting actually granted — the
ignore/dismiss family (`edac_dismiss_own_issues` and, for roles that meet its `edit_others_posts`
floor, `edac_dismiss_issues_globally`) — never the audit, export, full-site-scan, Explorer, or
highlighter capabilities it never conferred, and never the new site-wide `edac_dismiss_issues`. The
migration is floor-aware: it will not grant a capability to a legacy role that fails the capability's
floor. The
same boundary applies one-time capability **slug renames**: the `1.49.0` bump renames `ignore` →
`dismiss`, moving existing `edac_ignore_issues` grants to `edac_dismiss_own_issues` (their old
edit_post-gated behavior is own-scoped, so they are never promoted to the site-wide
`edac_dismiss_issues`) and `edac_ignore_issues_globally` to `edac_dismiss_issues_globally`, stripping
the retired slugs. The deprecated `EDAC_CAPABILITY_IGNORE_*` constants are retained.

**`reconcile()`** runs on `init`, self-heals the role/user grants from the options, and is where both
the defaults and migration are applied.

## Lifecycle: activation, deactivation, uninstall

- **Activating** an add-on registers its capabilities and, on first activation, seeds their defaults.
- **Deactivating** an add-on does **not** revoke its capabilities. A capability that leaves the bundle
  is intentionally left on whatever roles/users held it, so reactivating the add-on restores access
  seamlessly (`SyncCapability::reconcile()` no longer strips departed capabilities).
- **Uninstalling** cleans up. Capability cleanup lives **only in the free plugin's `uninstall.php`**,
  and only when the site's existing `edac_delete_data` setting is on. It removes the capability-system
  options and strips the managed capability set from every role. The managed set is computed as
  `role_map keys ∪ user_grants keys ∪ defaults_seeded keys` — **not** a broad `edac_*` prefix — so
  unrelated capabilities such as `edac_upload_pdf` are never touched, while orphans left by an add-on
  that was uninstalled earlier are still caught. Add-ons carry no capability cleanup of their own.

## Feature-specific enforcement notes

Most capabilities gate exactly one thing, but a few have extra rules worth calling out:

- **Dismiss issues** has two per-post tiers. `edac_dismiss_own_issues` requires **the capability AND
  `edit_post` on the specific post** — so an author may dismiss only on posts they can edit (their
  own), while an editor (who has `edit_others_posts`) covers every post. `edac_dismiss_issues` grants
  dismissing on **any** post regardless of ownership (a deliberate, opt-in superset — the "overstep").
  A single dismiss is allowed when the user holds `edac_dismiss_issues`, OR holds
  `edac_dismiss_own_issues` and can `edit_post` the target. The larger-blast-radius **global** dismiss
  (updating every post sharing a rule + element in one action) requires the separate
  `edac_dismiss_issues_globally` capability, whose floor is `edit_others_posts`; the `ignre_global`
  marker is only ever set by a holder of that global capability. Administrators pass via
  `manage_options`.
- **Full site scan** requires **`edit_others_posts` AND `edac_full_site_scan`**
  (`edacp_user_can_run_full_site_scan()`). The custom capability is an admin-controlled kill-switch
  layered on top of a hard security floor; it gates the scan page registration, its render, and all
  scan-control REST routes. A user granted only `edac_full_site_scan` without `edit_others_posts`
  cannot run a scan.
- **Saving a post's scan results** (the free `/post-scan-results/{id}` REST route) requires
  **`edit_post` on that specific post**, so single-post scans keep working for authors on their own
  posts, while editors (who have `edit_others_posts`) cover everything.
- **Exporting Audit History** requires **`edac_export_data` AND `edac_view_audit_history`**. Audit
  History is another feature's data, so the generic export capability alone does not expose it to a
  user who cannot otherwise view the audit log. The Issues, Scan-Stats, and Global-Ignores exports
  remain gated by `edac_export_data` alone.

## Cross-plugin helper API

The free plugin exposes `edac_user_can_*()` helper functions (in `includes/`). They look unused inside
the free plugin, but each add-on feature-detects them with `function_exists()` and calls them, falling
back to `manage_options` when running against an older free plugin that predates the helper.
**Do not remove them** — they are a load-bearing cross-plugin contract:

| Helper | Capability |
| --- | --- |
| `edac_user_can_dismiss_own_issues()` | `edac_dismiss_own_issues` |
| `edac_user_can_dismiss_issues()` | `edac_dismiss_issues` (any post) |
| `edac_user_can_dismiss_issues_globally()` | `edac_dismiss_issues_globally` |
| `edac_user_can_access_issues_explorer()` | `edac_issues_explorer_access` |
| `edac_user_can_view_audit_history()` | `edac_view_audit_history` |
| `edac_user_can_export_data()` | `edac_export_data` |
| `edac_user_can_run_full_site_scan()` | `edac_full_site_scan` (+ `edit_others_posts`) |

The former `edac_user_can_ignore()` / `edac_user_can_ignore_globally()` remain as **deprecated shims**
(add-ons still feature-detect the old names): `edac_user_can_ignore()` returns true when the user holds
either per-post dismiss capability; `edac_user_can_ignore_globally()` maps to
`edac_user_can_dismiss_issues_globally()`.
| `edac_user_can_use_frontend_highlighter()` | `edac_view_frontend_highlighter` |

Because the fallback only ever *widens* who can reach a page (to administrators) when a finer-grained
capability isn't recognized, no site is ever locked out of a page it could previously access.

## Extending: registering a capability from an add-on

Add-ons contribute capabilities through the `edac_capabilities` filter at load time (before the free
plugin assembles the registry on `plugins_loaded`):

```php
add_filter(
    'edac_capabilities',
    function ( $capabilities ) {
        $capabilities['edac_my_feature'] = [
            'label'         => __( 'My feature', 'my-add-on' ),
            'description'   => __( 'What this capability allows.', 'my-add-on' ),
            'group'         => __( 'My Add-on', 'my-add-on' ),
            'owner'         => 'my-add-on',
            'pro'           => true,
            'floor'         => 'edit_others_posts', // or '' for no floor
            'default_roles' => [], // roles auto-granted on first activation; keep this
                                   // conservative — administrators can grant explicitly.
        ];
        return $capabilities;
    }
);
```

Once registered, the free plugin syncs the capability onto the assigned roles/users, shows it in the
Permissions matrix under its `group`, seeds its `default_roles` on first activation, and enforces its
`floor` everywhere.

## Granting a capability to one specific user

There is no dedicated admin screen for granting one of these capabilities to an individual user,
independent of their role (the engine supports per-user grants programmatically via
`edac_capability_user_grants`, but the UI for it was removed). If you need to grant one Editor access
without granting the whole role, WordPress's own user-capability API works directly and coexists
safely with the role matrix: a capability added straight to a user's account is stored separately from
anything granted via their role, so later changes to the role matrix never remove an
individually-granted capability, and vice versa.

Add a snippet like this to a site-specific plugin (not a theme's `functions.php`, since switching
themes would silently remove it):

```php
// Grant the capability to one user by ID.
$user = get_user_by( 'id', 123 );
if ( $user ) {
    $user->add_cap( 'edac_view_audit_history' );
}
```

To revoke it later:

```php
$user = get_user_by( 'id', 123 );
if ( $user ) {
    $user->remove_cap( 'edac_view_audit_history' );
}
```

Any of the capability slugs in the [registered capabilities](#registered-capabilities) table can be
used the same way. Note that a capability whose floor the user's role does not meet will still be
refused by the sync engine on the next `reconcile()` if granted through the role matrix; a direct
per-user `add_cap()` bypasses the floor, so use it deliberately.
