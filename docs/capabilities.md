# Capability Reference

The Accessibility Checker plugin family gates every non-public feature behind either a stock WordPress
capability (`manage_options`, `edit_posts`, etc.) or one of the custom capabilities documented below.
All of the custom capabilities are granted together, as a bundle, from a single control surface — there
is no separate settings screen per capability.

## The ignore-roles capability bundle

Under **Accessibility Checker → Settings**, the role-select field that grants "can ignore issues" grants
all five of the following capabilities to the selected roles at once:

| Capability | Gates | Plugin |
| --- | --- | --- |
| `edac_ignore_issues` | Per-post ignore/dismiss actions | accessibility-checker (free) |
| `edac_ignore_issues_globally` | "Dismiss Globally" — suppresses an issue across every post sharing a rule+object, not just the one being viewed | accessibility-checker-pro |
| `edac_issues_explorer_access` | Getting into the Issues Explorer app at all | accessibility-checker-pro |
| `edac_view_audit_history` | The Audit History admin page and its REST data route (including CSV export) | accessibility-checker-audit-history |
| `edac_export_data` | The Export Data admin page and all of its export actions (Issues, Scan Stats, Global Ignores, Audit History) | accessibility-checker-export |

Administrators (`manage_options`) always pass every one of these checks, regardless of whether their
role was explicitly included in the setting.

Both audit-history and export update independently of the free plugin, so each falls back to requiring
`manage_options` if it detects an older free-plugin version that predates the capability it needs. No
site is ever locked out of a page it could previously access — the fallback only ever widens who can
reach a page it doesn't recognize a finer-grained capability for, never narrows it.

## Granting a capability to one specific user

There is currently no dedicated admin screen for granting one of these capabilities to an individual
user, independent of their role. If you need that — for example, one specific Editor should be able to
view Audit History without granting the capability to every Editor — WordPress's own user-capability
API already supports it directly, and it coexists safely with the role-based bundle above: a capability
added directly to a user's account is stored separately from anything granted via their role, so a later
change to the role-level setting will never remove an individually-granted capability, and vice versa.

Add a snippet like this to a site-specific plugin (do not add it to a theme's `functions.php`, since
switching themes would silently remove it):

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

Any of the five capability strings in the table above can be used the same way.
