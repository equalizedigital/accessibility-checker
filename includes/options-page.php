<?php
/**
 * Accessibility Checker plugin file.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Purge_Post_Data;
use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Settings;
use EDAC\Inc\Accessibility_Statement;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage;
use EqualizeDigital\AccessibilityChecker\Capabilities\CapabilityChecker;
use EqualizeDigital\AccessibilityChecker\Capabilities\SyncCapability;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Per-post dismiss capabilities. "own" is scoped by edit_post (authors dismiss
// their own posts, editors any post); the less-specific "dismiss issues" grants
// dismissing on ANY post regardless of ownership (a superset of own, and a
// deliberate opt-in overstep of core edit_post).
defined( 'EDAC_CAPABILITY_DISMISS_OWN_ISSUES' ) || define( 'EDAC_CAPABILITY_DISMISS_OWN_ISSUES', 'edac_dismiss_own_issues' );
defined( 'EDAC_CAPABILITY_DISMISS_ISSUES' ) || define( 'EDAC_CAPABILITY_DISMISS_ISSUES', 'edac_dismiss_issues' );
// Larger blast radius than a per-post dismiss: suppresses an issue across
// every post sharing a rule+object, not just the one being viewed.
defined( 'EDAC_CAPABILITY_DISMISS_ISSUES_GLOBALLY' ) || define( 'EDAC_CAPABILITY_DISMISS_ISSUES_GLOBALLY', 'edac_dismiss_issues_globally' );

// Deprecated legacy slugs (renamed ignore -> dismiss). Retained so the rename
// migration can find old grants and any external code referencing the constants
// does not fatal. edac_ignore_issues migrates to edac_dismiss_own_issues.
defined( 'EDAC_CAPABILITY_IGNORE_ISSUES' ) || define( 'EDAC_CAPABILITY_IGNORE_ISSUES', 'edac_ignore_issues' );
defined( 'EDAC_CAPABILITY_IGNORE_ISSUES_GLOBALLY' ) || define( 'EDAC_CAPABILITY_IGNORE_ISSUES_GLOBALLY', 'edac_ignore_issues_globally' );
// Gates getting into pro's Issues Explorer app at all, independent of
// whether the user can also ignore issues once inside it.
defined( 'EDAC_CAPABILITY_ISSUES_EXPLORER_ACCESS' ) || define( 'EDAC_CAPABILITY_ISSUES_EXPLORER_ACCESS', 'edac_issues_explorer_access' );
// Gates the accessibility-checker-audit-history plugin's admin page and
// history REST route (including the CSV export path).
defined( 'EDAC_CAPABILITY_VIEW_AUDIT_HISTORY' ) || define( 'EDAC_CAPABILITY_VIEW_AUDIT_HISTORY', 'edac_view_audit_history' );
// Gates the accessibility-checker-export plugin's admin page and all of its
// admin-post export handlers.
defined( 'EDAC_CAPABILITY_EXPORT_DATA' ) || define( 'EDAC_CAPABILITY_EXPORT_DATA', 'edac_export_data' );
// Gates running the (pro) Full Site Scan: its scan-control REST routes and
// saving scan results for posts the user could not otherwise edit. A full
// site scan is inherently site-wide, so this grant lets a non-editor role
// scan and store results for content it does not own.
defined( 'EDAC_CAPABILITY_FULL_SITE_SCAN' ) || define( 'EDAC_CAPABILITY_FULL_SITE_SCAN', 'edac_full_site_scan' );
// Gates loading the front-end accessibility highlighter for a user who cannot
// otherwise edit the post being viewed. The highlighter overlays this post's
// issues on the live page; this grant lets a non-editor reviewer role see it
// on any scannable, readable content.
defined( 'EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER' ) || define( 'EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER', 'edac_view_frontend_highlighter' );

// Plugin version at which the capability bundle migration runs. Sites upgrading
// the free plugin from below this version get a one-time forced re-sync of the
// bundle onto their configured roles, plus any pending capability slug renames
// (see SyncCapability::reconcile()). Bump this whenever a migration must re-run
// (the 1.48.0 bump carries the ignore -> dismiss rename). The shipping plugin
// version must be >= this value.
defined( 'EDAC_CAPABILITY_MIGRATION_VERSION' ) || define( 'EDAC_CAPABILITY_MIGRATION_VERSION', '1.48.0' );

/**
 * Metadata for every Accessibility Checker capability, keyed by slug.
 *
 * This is the single registry that drives both the sync bundle and the
 * Permissions admin UI. The free plugin contributes its own capabilities;
 * add-on plugins (Pro, Export, Audit History) contribute theirs via the
 * edac_capabilities filter, so activating or deactivating an add-on changes
 * the set and the next reconcile grants or revokes its capabilities.
 *
 * Each entry is [ 'label', 'description', 'group', 'owner', 'pro' ]. Any slug
 * still contributed only through the legacy edac_capability_bundle filter is
 * included with synthesized fallback metadata so it is never dropped.
 *
 * @return array<string, array{label:string,description:string,group:string,owner:string,pro:bool}>
 */
function edac_capability_metadata(): array {
	static $capabilities_cache = null;
	if ( null !== $capabilities_cache ) {
		return $capabilities_cache;
	}

	/**
	 * Filter the Accessibility Checker capability registry.
	 *
	 * Add-on plugins register their capabilities' metadata here (contribute at
	 * load time so the capability is present whenever the registry is assembled,
	 * regardless of plugin load order).
	 *
	 * @since 1.xx.x
	 *
	 * @param array<string, array> $capabilities Capability metadata keyed by slug.
	 */
	$capabilities = apply_filters(
		'edac_capabilities',
		[
			EDAC_CAPABILITY_DISMISS_OWN_ISSUES        => [
				'label'         => __( 'Dismiss own issues', 'accessibility-checker' ),
				'description'   => __( 'Dismiss and reopen accessibility issues on posts the user can edit (their own posts; editors can edit any).', 'accessibility-checker' ),
				'group'         => __( 'Accessibility Checker', 'accessibility-checker' ),
				'owner'         => 'accessibility-checker',
				'pro'           => false,
				'floor'         => 'edit_posts',
				'default_roles' => [ 'editor', 'author', 'contributor' ],
			],
			EDAC_CAPABILITY_DISMISS_ISSUES            => [
				'label'         => __( 'Dismiss issues (any post)', 'accessibility-checker' ),
				'description'   => __( 'Dismiss and reopen accessibility issues on any post, even if the user cannot otherwise edit it.', 'accessibility-checker' ),
				'group'         => __( 'Accessibility Checker', 'accessibility-checker' ),
				'owner'         => 'accessibility-checker',
				'pro'           => false,
				'floor'         => 'edit_posts',
				'default_roles' => [],
			],
			EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER => [
				'label'         => __( 'Front-end highlighter', 'accessibility-checker' ),
				'description'   => __( 'View the front-end accessibility highlighter on published content.', 'accessibility-checker' ),
				'group'         => __( 'Accessibility Checker', 'accessibility-checker' ),
				'owner'         => 'accessibility-checker',
				'pro'           => false,
				'floor'         => '',
				'default_roles' => [ 'editor', 'author' ],
			],
		]
	);

	$capabilities = is_array( $capabilities ) ? $capabilities : [];

	// Back-compat: fold in any slug contributed only through the deprecated
	// edac_capability_bundle filter, with synthesized metadata so add-ons that
	// have not adopted edac_capabilities yet are still assignable and synced.
	$legacy = apply_filters_deprecated(
		'edac_capability_bundle',
		[ [] ],
		'1.xx.x',
		'edac_capabilities'
	);
	foreach ( (array) $legacy as $slug ) {
		$slug = (string) $slug;
		if ( '' !== $slug && ! isset( $capabilities[ $slug ] ) ) {
			$capabilities[ $slug ] = [
				'label'         => ucwords( str_replace( [ 'edac_', '_' ], [ '', ' ' ], $slug ) ),
				'description'   => '',
				'group'         => '',
				'owner'         => '',
				'pro'           => false,
				'floor'         => '',
				'default_roles' => [],
			];
		}
	}

	// Normalize every entry so consumers can rely on all keys existing.
	foreach ( $capabilities as $slug => $meta ) {
		$capabilities[ $slug ] = array_merge(
			[
				'label'         => (string) $slug,
				'description'   => '',
				'group'         => '',
				'owner'         => '',
				'pro'           => false,
				'floor'         => '',
				'default_roles' => [],
			],
			is_array( $meta ) ? $meta : []
		);
	}

	$capabilities_cache = $capabilities;
	return $capabilities_cache;
}

/**
 * The capability bundle: the slugs synced onto roles/users by the permission
 * system. Derived from edac_capability_metadata() so the registry is the one
 * source of truth for both syncing and the admin UI.
 *
 * @return string[] Sorted, de-duplicated capability slugs.
 */
function edac_capability_bundle(): array {
	$capabilities = array_values( array_filter( array_map( 'strval', array_keys( edac_capability_metadata() ) ) ) );
	sort( $capabilities );

	return $capabilities;
}

/**
 * Whether a capability can be assigned from the Permissions UI.
 *
 * A capability is always assignable once its owning plugin is active (i.e. it is
 * in the registry), except that Accessibility Checker Pro capabilities require a
 * valid Pro license. Add-ons that gate their own capabilities behind a separate
 * license can lock their rows via the edac_capability_is_editable filter.
 *
 * @param string $slug The capability slug.
 * @param array  $meta The capability metadata (from edac_capability_metadata()).
 * @return bool
 */
function edac_capability_is_editable( string $slug, array $meta = [] ): bool {
	$editable = true;

	if ( 'accessibility-checker-pro' === ( $meta['owner'] ?? '' ) && ! ( defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) ) {
		$editable = false;
	}

	/**
	 * Filter whether a capability is editable in the Permissions UI.
	 *
	 * Locked rows are rendered disabled and their stored assignments are left
	 * untouched on save.
	 *
	 * @since 1.xx.x
	 *
	 * @param bool   $editable Whether the capability can be assigned.
	 * @param string $slug     The capability slug.
	 * @param array  $meta     The capability metadata.
	 */
	return (bool) apply_filters( 'edac_capability_is_editable', $editable, $slug, $meta );
}

/**
 * Whether a role's live capability set satisfies a capability's floor.
 *
 * The "floor" is the WordPress capability a role must already have before an
 * edac_* capability may be assigned to it (e.g. edac_dismiss_issues_globally
 * requires edit_others_posts). This is checked against the role's actual, live
 * capabilities so it respects sites that customize roles via other plugins,
 * rather than assuming what a stock role can do. An empty floor is always met.
 *
 * @param string $role_slug The role slug (e.g. 'editor').
 * @param string $floor     The required WordPress capability, or '' for none.
 * @return bool
 */
function edac_role_meets_floor( string $role_slug, string $floor ): bool {
	if ( '' === $floor ) {
		return true;
	}

	$role = wp_roles()->get_role( $role_slug );

	return $role && ! empty( $role->capabilities[ $floor ] );
}

/**
 * A human-readable description of a floor capability, for the Permissions UI.
 *
 * @param string $floor The required WordPress capability, or '' for none.
 * @return string Empty string when there is no floor.
 */
function edac_floor_requirement_label( string $floor ): string {
	$labels = [
		'edit_posts'        => __( 'the ability to edit posts', 'accessibility-checker' ),
		'edit_others_posts' => __( "the ability to edit other users' posts", 'accessibility-checker' ),
	];

	if ( '' === $floor ) {
		return '';
	}

	/* translators: %s: a human-readable capability requirement, e.g. "the ability to edit posts". */
	return sprintf( __( 'Requires %s.', 'accessibility-checker' ), $labels[ $floor ] ?? $floor );
}

/**
 * Roles that can be assigned Accessibility Checker capabilities in the UI.
 *
 * Roles that can manage_options (administrators and equivalents) are excluded:
 * they already pass every edac_* capability via the admin bypass, so showing
 * them would be a no-op the admin cannot turn off.
 *
 * Deliberately reimplements get_editable_roles() (wp-admin/includes/user.php:
 * apply_filters( 'editable_roles', wp_roles()->roles )) rather than calling it -
 * that file is only autoloaded within the wp-admin bootstrap (is_admin()), so
 * calling the real function here would fatal on any caller outside it. This
 * function runs from edac_sanitize_capability_role_map(), which fires on every
 * update_option( 'edac_capability_role_map', ... ) via the sanitize_option
 * filter - including REST routes (e.g. the multisite settings-clone route) and
 * WP-CLI, neither of which load wp-admin.
 *
 * @return array<string, array> Editable roles keyed by slug, minus admins.
 */
function edac_assignable_roles(): array {
	/** This filter is documented in wp-admin/includes/user.php. */
	$editable_roles = apply_filters( 'editable_roles', wp_roles()->roles );

	return array_filter(
		$editable_roles,
		function ( $role ) {
			return empty( $role['capabilities']['manage_options'] );
		}
	);
}

/**
 * The default capability => roles map for a fresh install.
 *
 * Built from each capability's declared default_roles, filtered to roles that
 * actually meet the capability's floor. Add-ons declare their own defaults via
 * the edac_capabilities metadata filter.
 *
 * @return array<string, string[]> Capability slug => role slugs.
 */
function edac_default_capability_role_map(): array {
	$map = [];

	foreach ( edac_capability_metadata() as $slug => $meta ) {
		$defaults = array_values(
			array_filter(
				(array) $meta['default_roles'],
				function ( $role_slug ) use ( $meta ) {
					return edac_role_meets_floor( $role_slug, $meta['floor'] );
				}
			)
		);

		if ( $defaults ) {
			$map[ $slug ] = $defaults;
		}
	}

	return $map;
}

/**
 * Seed the default capability role map on a genuinely fresh install.
 *
 * Called once from edac_activation() (which detects a first install from the
 * absence of a prior edac_activation_date). Seeding lives at activation, not on
 * init, so the two install paths are cleanly separated by their entry point:
 *
 * - A FRESH install runs the activation hook -> this seeds the full default suite
 *   and stamps the capability migration as already satisfied, so the init-time
 *   migration in SyncCapability::reconcile() never runs against it.
 * - A site UPGRADING from the pre-capability release does NOT run the activation
 *   hook (activation hooks do not fire on plugin update); it is handled entirely
 *   by that version-gated migration, which carries over only the grants the legacy
 *   "Ignore Permissions" setting actually gave and never the fresh-install
 *   defaults.
 *
 * This replaces an init-time seeder whose fresh-vs-migrating detection had to
 * guess from option state - and mis-fired, because edac_activation() used to seed
 * edacp_ignore_user_roles = ['administrator'], making every fresh install look
 * like a migrating one and silently skip its default grants.
 *
 * @return void
 */
function edac_seed_capability_defaults_on_install(): void {
	$defaults = edac_default_capability_role_map();

	update_option( 'edac_capability_role_map', $defaults );
	// Record every registered capability as seeded so its default is never offered
	// again - an admin who later unchecks one is respected.
	update_option(
		'edac_capability_defaults_seeded',
		array_values( array_filter( array_map( 'strval', array_keys( edac_capability_metadata() ) ) ) )
	);
	// Stamp the migration as satisfied: a fresh install has no legacy config, and
	// this marks it current so reconcile() can never treat it as a legacy site.
	update_option( 'edac_capability_migration_version_edac_capability_role_map', EDAC_CAPABILITY_MIGRATION_VERSION );

	// Apply immediately so roles carry the granted caps without waiting for init.
	edac_ignore_capability()->sync_matrix( $defaults );
}

/**
 * Validate an incoming capability role map into a safe, storable one.
 *
 * Shared by the Permissions save handler and the settings importer so both paths
 * apply IDENTICAL rules - a hand-edited import file can never grant more than the
 * UI would. Starting from the stored map preserves entries for locked (upsell) or
 * inactive-add-on capabilities; for every editable capability the incoming roles
 * are narrowed to assignable roles (administrators excluded) that meet the
 * capability's floor on THIS site. So an import can never, for example, grant
 * edac_dismiss_issues_globally to subscriber.
 *
 * Also wired as the `sanitize_option_edac_capability_role_map` filter, so the pro
 * settings importer - which runs the value through sanitize_option() - validates
 * the map through here automatically.
 *
 * @param mixed $incoming The incoming [ capability => [role, …] ] map.
 * @return array<string, string[]> The validated map.
 */
function edac_sanitize_capability_role_map( $incoming ): array {
	$incoming       = is_array( $incoming ) ? $incoming : [];
	$metadata       = edac_capability_metadata();
	$editable_roles = array_keys( edac_assignable_roles() );

	// Start from the stored map so locked/inactive-add-on entries are preserved.
	$role_map = get_option( 'edac_capability_role_map', [] );
	$role_map = is_array( $role_map ) ? $role_map : [];

	foreach ( $metadata as $slug => $meta ) {
		// Locked (upsell) rows are not accepted from input; leave the stored value.
		if ( ! edac_capability_is_editable( $slug, $meta ) ) {
			continue;
		}

		$roles = isset( $incoming[ $slug ] ) ? array_map( 'sanitize_key', (array) $incoming[ $slug ] ) : [];
		$roles = array_values( array_intersect( $roles, $editable_roles ) );
		// Floor re-validation: a role that does not meet the capability's floor on
		// this site can never be granted it, even from a hand-edited import file.
		$roles = array_values(
			array_filter(
				$roles,
				function ( $role_slug ) use ( $meta ) {
					return edac_role_meets_floor( $role_slug, $meta['floor'] );
				}
			)
		);

		if ( $roles ) {
			$role_map[ $slug ] = $roles;
		} else {
			unset( $role_map[ $slug ] );
		}
	}

	return $role_map;
}
add_filter( 'sanitize_option_edac_capability_role_map', 'edac_sanitize_capability_role_map' );

/**
 * Apply the stored capability role map onto this site's roles right now.
 *
 * A public trigger for the role sync, for any caller that writes the role map
 * option outside the normal admin-post save (e.g. a WP-CLI command, a custom
 * migration script). reconcile() only re-syncs on a bundle/version change, so
 * a changed role map needs this explicit apply.
 *
 * Deliberately NOT wired to travel with the pro settings import/export or the
 * multisite settings clone - permissions/capability grants are security-
 * relevant and site-specific, so both of those flows now exclude this option
 * and direct the admin to configure it manually on each site instead.
 *
 * @return void
 */
function edac_sync_capability_roles(): void {
	edac_ignore_capability()->sync_matrix( (array) get_option( 'edac_capability_role_map', [] ) );
}

/**
 * The SyncCapability instance managing the bundle. Assembled on init
 * (see below) so every active add-on has contributed to edac_capability_bundle
 * first; also usable directly (e.g. in tests) as a lazy singleton.
 *
 * @return SyncCapability
 */
function edac_ignore_capability(): SyncCapability {
	static $capability = null;

	if ( null === $capability ) {
		// Precompute each capability's floor once (all active add-ons have
		// contributed metadata by the time this runs on init), so the
		// floor policy passed to the engine is a cheap array lookup rather than a
		// metadata rebuild per role/capability during sync.
		$floors = [];
		foreach ( edac_capability_metadata() as $slug => $meta ) {
			$floors[ $slug ] = (string) $meta['floor'];
		}

		$capability = new SyncCapability(
			edac_capability_bundle(),
			'edac_capability_role_map',    // Per-capability role assignments.
			EDAC_CAPABILITY_MIGRATION_VERSION,
			'edacp_ignore_user_roles',     // Legacy option seeded into the map on migration.
			// Floor policy: never grant a capability to a role whose live
			// capabilities do not meet that capability's floor.
			function ( $role_slug, $cap ) use ( $floors ) {
				return edac_role_meets_floor( (string) $role_slug, $floors[ $cap ] ?? '' );
			},
			// One-time slug renames applied on the migration boundary. The old
			// edit_post-gated "ignore" grants were own-scoped, so they migrate to
			// the new own-dismiss capability (never the site-wide one).
			[
				EDAC_CAPABILITY_IGNORE_ISSUES          => EDAC_CAPABILITY_DISMISS_OWN_ISSUES,
				EDAC_CAPABILITY_IGNORE_ISSUES_GLOBALLY => EDAC_CAPABILITY_DISMISS_ISSUES_GLOBALLY,
			],
			// The legacy "Ignore Permissions" (edacp_ignore_user_roles) setting only
			// granted the ignore/dismiss family, so a legacy role is seeded ONLY
			// these on migration - never the audit/export/scan/explorer/highlighter
			// capabilities it never conferred. Floors still apply (global dismiss
			// only reaches roles with edit_others_posts).
			[
				EDAC_CAPABILITY_DISMISS_OWN_ISSUES,
				EDAC_CAPABILITY_DISMISS_ISSUES_GLOBALLY,
			]
		);
		$capability->register();
	}

	return $capability;
}
// Assemble after all plugins have loaded so add-ons can contribute to the
// bundle via the filter regardless of plugin load order. Hooked to init
// (rather than plugins_loaded) because edac_capability_metadata() calls
// translation functions, and add-ons register their edac_capabilities filter
// callbacks via add_filter() at plugin load time, so they're already in
// place well before init fires either way.
add_action( 'init', 'edac_ignore_capability', 20 );

// Register the Permissions page request handler (admin-post save) on every
// request. The tab UI itself is wired later on admin_menu.
add_action(
	'init',
	function () {
		$settings_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );
		( new PermissionsPage( $settings_capability ) )->register_request_handlers();
	},
	21
);

/*
 * Capability reader helpers (edac_user_can_*).
 *
 * IMPORTANT: these are a cross-plugin API and must not be removed as "unused" —
 * they have few or no call sites inside the free plugin itself, but each add-on
 * (Pro, Export in includes/, Audit History in app/) feature-detects them with
 * function_exists() and calls them, falling back to manage_options when the
 * installed free-plugin version predates the capability. Deleting one silently
 * downgrades that feature's gate to manage_options for older/paired installs.
 */

/**
 * Check if user can dismiss issues on posts they can edit (their own), or can
 * manage options.
 *
 * @return bool
 */
function edac_user_can_dismiss_own_issues() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_DISMISS_OWN_ISSUES );
}

/**
 * Check if user can dismiss issues on ANY post (regardless of ownership), or can
 * manage options. A superset of edac_user_can_dismiss_own_issues().
 *
 * @return bool
 */
function edac_user_can_dismiss_issues() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_DISMISS_ISSUES );
}

/**
 * Check if user can globally dismiss an issue (suppress it across every post
 * sharing a rule+object) or can manage options.
 *
 * @return bool
 */
function edac_user_can_dismiss_issues_globally() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_DISMISS_ISSUES_GLOBALLY );
}

/**
 * Deprecated: use edac_user_can_dismiss_issues()/edac_user_can_dismiss_own_issues().
 *
 * Retained as a cross-plugin shim: add-ons feature-detect this name. "Can the
 * user dismiss at all" = holds either per-post dismiss capability.
 *
 * @return bool
 */
function edac_user_can_ignore() {
	return edac_user_can_dismiss_issues() || edac_user_can_dismiss_own_issues();
}

/**
 * Deprecated: use edac_user_can_dismiss_issues_globally(). Retained as a
 * cross-plugin shim for add-ons that feature-detect this name.
 *
 * @return bool
 */
function edac_user_can_ignore_globally() {
	return edac_user_can_dismiss_issues_globally();
}

/**
 * Check if user can access the (pro) Issues Explorer, or can manage options.
 *
 * @return bool
 */
function edac_user_can_access_issues_explorer() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_ISSUES_EXPLORER_ACCESS );
}

/**
 * Check if user can view the (audit-history) audit trail, or can manage options.
 *
 * @return bool
 */
function edac_user_can_view_audit_history() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_VIEW_AUDIT_HISTORY );
}

/**
 * Check if user can use the (export) data export plugin, or can manage options.
 *
 * @return bool
 */
function edac_user_can_export_data() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_EXPORT_DATA );
}

/**
 * Check if user can run the (pro) Full Site Scan, or can manage options.
 *
 * Consumers that need to preserve the historical editor-level access to the
 * scanner should OR this with current_user_can( 'edit_others_posts' ) at the
 * call site; this helper reports only the dedicated capability (plus the
 * manage_options bypass), matching the other edac_user_can_*() helpers.
 *
 * @return bool
 */
function edac_user_can_run_full_site_scan() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_FULL_SITE_SCAN );
}

/**
 * Check if user can load the front-end accessibility highlighter, or can
 * manage options.
 *
 * Consumers that need to preserve the historical editor-level access should OR
 * this with current_user_can( 'edit_post', $post_id ) at the call site; this
 * helper reports only the dedicated capability (plus the manage_options
 * bypass), matching the other edac_user_can_*() helpers.
 *
 * @return bool
 */
function edac_user_can_use_frontend_highlighter() {
	return CapabilityChecker::user_can( EDAC_CAPABILITY_VIEW_FRONTEND_HIGHLIGHTER );
}

/**
 * Whether the current user should see the Accessibility Checker top-level menu.
 *
 * The parent `accessibility_checker` menu is the mount point every add-on submenu
 * (Issues Explorer, Audit History, Export Data, Full Site Scan) hangs off via
 * add_submenu_page(). If the parent is never registered in a request, those child
 * pages silently vanish no matter what capability each one individually checks. So
 * the parent must register whenever the user can reach ANY of its pages - not only
 * for edit_posts users (PRO-1283).
 *
 * Returns true for edit_posts (the historical gate) OR for a holder of any
 * Accessibility Checker capability in the bundle - dismiss, Issues Explorer,
 * audit-history, export, full-site-scan, highlighter - which covers add-on
 * capabilities synced onto lower roles such as Subscriber. Checking only the
 * dismiss capability (as an earlier fix proposed) would miss exactly the
 * view/access capabilities that are the point of the bug.
 *
 * @return bool
 */
function edac_user_can_see_admin_menu(): bool {
	if ( current_user_can( 'edit_posts' ) ) {
		return true;
	}

	foreach ( edac_capability_bundle() as $capability ) {
		if ( CapabilityChecker::user_can( $capability ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Add an options page under the Settings submenu
 */
function edac_add_options_page() {

	// Register the menu for edit_posts users and for anyone holding an
	// Accessibility Checker capability (so add-on pages granted to lower roles are
	// reachable). The parent menu lands on the informational Welcome page.
	if ( ! edac_user_can_see_admin_menu() ) {
		return;
	}

	add_menu_page(
		__( 'Welcome to Accessibility Checker', 'accessibility-checker' ),
		__( 'Accessibility Checker', 'accessibility-checker' ),
		'read',
		'accessibility_checker',
		'edac_display_welcome_page',
		'dashicons-universal-access-alt'
	);

	if ( ! edac_user_can_dismiss_issues() && ! edac_user_can_dismiss_own_issues() ) {
		return;
	}

	/**
	 * Filter the capability required to access the settings page.
	 *
	 * @since 1.4.0
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	$settings_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );

	add_submenu_page(
		'accessibility_checker',
		__( 'Accessibility Checker Settings', 'accessibility-checker' ),
		__( 'Settings', 'accessibility-checker' ),
		$settings_capability,
		'accessibility_checker_settings',
		'edac_display_options_page'
		// The submenu doesn't typically require a separate icon.
	);

	$fixes_page = new FixesPage( $settings_capability );
	$fixes_page->add_page();

	$permissions_page = new PermissionsPage( $settings_capability );
	$permissions_page->add_page();

	// Deregister the pro setting registration - this is added for backwards compatibility for users
	// that don't update pro. Added here in 1.31.0 and released with pro 1.16.0.
	add_action(
		'admin_init',
		function () {
			// Remove the pro plugin's settings registration entirely.
			remove_action( 'admin_init', 'edacp_register_setting', 11 );
		}
	);
}

/**
 * Render the welcome page for plugin
 */
function edac_display_welcome_page() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/welcome-page.php';
}

/**
 * Render the options page for plugin
 */
function edac_display_options_page() {
	include_once plugin_dir_path( __DIR__ ) . 'partials/settings-page.php';
}

/**
 * Register settings
 */
function edac_register_setting() {

	// Add sections.
	add_settings_section(
		'edac_general',
		__( 'Scan Settings', 'accessibility-checker' ),
		'edac_general_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_simplified_summary',
		__( 'Simplified Summary Settings', 'accessibility-checker' ),
		'edac_simplified_summary_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_footer_accessibility_statement',
		__( 'Footer Accessibility Statement', 'accessibility-checker' ),
		'edac_footer_accessibility_statement_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_frontend_highlighter',
		__( 'Frontend Accessibility Checker', 'accessibility-checker' ),
		'edac_frontend_highlighter_section_cb',
		'edac_settings'
	);

	add_settings_section(
		'edac_system',
		__( 'System Settings', 'accessibility-checker' ),
		'edac_system_cb',
		'edac_settings'
	);

	// Add fields.
	add_settings_field(
		'edac_post_types',
		__( 'Post Types To Be Checked', 'accessibility-checker' ),
		'edac_post_types_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edac_post_types' ]
	);

	add_settings_field(
		'edacp_full_site_scan_speed',
		__( 'Scan Speed', 'accessibility-checker' ),
		'edac_full_site_scan_speed_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edacp_full_site_scan_speed' ]
	);

	add_settings_field(
		'edacp_enable_archive_scanning',
		__( 'Archive Scanning', 'accessibility-checker' ),
		'edac_enable_archive_scanning_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edacp_enable_archive_scanning' ]
	);

	add_settings_field(
		'edacp_scan_all_taxonomies',
		__( 'Taxonomy Scanning', 'accessibility-checker' ),
		'edac_scan_all_taxonomy_terms_cb',
		'edac_settings',
		'edac_general',
		[ 'label_for' => 'edacp_scan_all_taxonomies' ]
	);

	add_settings_field(
		'edac_delete_data',
		__( 'Delete Data', 'accessibility-checker' ),
		'edac_delete_data_cb',
		'edac_settings',
		'edac_system',
		[ 'label_for' => 'edac_delete_data' ]
	);

	add_settings_field(
		'edac_show_metabox_in_block_editor',
		__( 'Block Editor Metabox', 'accessibility-checker' ),
		'edac_show_metabox_in_block_editor_cb',
		'edac_settings',
		'edac_system',
		[ 'label_for' => 'edac_show_metabox_in_block_editor' ]
	);

	add_settings_field(
		'edac_simplified_summary_prompt',
		__( 'Prompt for Simplified Summary', 'accessibility-checker' ),
		'edac_simplified_summary_prompt_cb',
		'edac_settings',
		'edac_simplified_summary',
		[ 'label_for' => 'edac_simplified_summary_prompt' ]
	);

	add_settings_field(
		'edac_simplified_summary_position',
		__( 'Simplified Summary Position', 'accessibility-checker' ),
		'edac_simplified_summary_position_cb',
		'edac_settings',
		'edac_simplified_summary',
		[ 'label_for' => 'edac_simplified_summary_position' ]
	);

	add_settings_field(
		'edacp_simplified_summary_heading',
		__( 'Simplified Summary Heading', 'accessibility-checker' ),
		'edac_simplified_summary_heading_cb',
		'edac_settings',
		'edac_simplified_summary',
		[ 'label_for' => 'edacp_simplified_summary_heading' ]
	);

	add_settings_field(
		'edac_add_footer_accessibility_statement',
		__( 'Add Footer Accessibility Statement', 'accessibility-checker' ),
		'edac_add_footer_accessibility_statement_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_add_footer_accessibility_statement' ]
	);

	add_settings_field(
		'edac_include_accessibility_statement_link',
		__( 'Include Link to Accessibility Policy', 'accessibility-checker' ),
		'edac_include_accessibility_statement_link_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_include_accessibility_statement_link' ]
	);

	add_settings_field(
		'edac_accessibility_policy_page',
		__( 'Accessibility Policy page', 'accessibility-checker' ),
		'edac_accessibility_policy_page_cb',
		'edac_settings',
		'edac_footer_accessibility_statement',
		[ 'label_for' => 'edac_accessibility_policy_page' ]
	);

	add_settings_field(
		'edac_accessibility_statement_preview',
		__( 'Accessibility Statement Preview', 'accessibility-checker' ),
		'edac_accessibility_statement_preview_cb',
		'edac_settings',
		'edac_footer_accessibility_statement'
	);

	add_settings_field(
		'edac_frontend_highlighter_position',
		__( 'Frontend Accessibility Checker Position', 'accessibility-checker' ),
		'edac_frontend_highlighter_position_cb',
		'edac_settings',
		'edac_frontend_highlighter',
		[ 'label_for' => 'edac_frontend_highlighter_position' ]
	);

	// Register settings.
	register_setting( 'edac_settings', 'edac_post_types', 'edac_sanitize_post_types' );

	register_setting( 'edac_settings', 'edac_delete_data', 'edac_sanitize_checkbox' );
	register_setting( 'edac_settings', 'edac_show_metabox_in_block_editor', 'edac_sanitize_checkbox' );
	register_setting(
		'edac_settings',
		'edac_simplified_summary_prompt',
		[
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_prompt',
			'default'           => 'when required',
		]
	);
	register_setting(
		'edac_settings',
		'edac_simplified_summary_position',
		[
			'type'              => 'string',
			'sanitize_callback' => 'edac_sanitize_simplified_summary_position',
			'default'           => 'after',
		]
	);
	register_setting( 'edac_settings', 'edac_add_footer_accessibility_statement', 'edac_sanitize_checkbox' );
	register_setting( 'edac_settings', 'edac_include_accessibility_statement_link', 'edac_sanitize_checkbox' );
	register_setting( 'edac_settings', 'edac_accessibility_policy_page', 'edac_sanitize_accessibility_policy_page' );

	register_setting( 'edac_settings', 'edac_frontend_highlighter_position', 'edac_sanitize_frontend_highlighter_position' );

	// Upsell settings - these are using edacp prefix for backwards compatibility.
	register_setting( 'edac_settings', 'edacp_full_site_scan_speed', 'edac_sanitize_pro_scan_speed' );
	register_setting( 'edac_settings', 'edacp_enable_archive_scanning', 'edac_sanitize_pro_archive_scanning' );
	// Option keys here MUST match the keys the pro scanner reads, or the setting is
	// inert: VirtualContent\VirtualItemManager reads edacp_enable_archive_scanning and
	// VirtualContent\Scannable\Taxonomy reads edacp_scan_all_taxonomies. The taxonomy
	// field previously wrote edacp_scan_all_taxonomy_terms, a key nothing read, so the
	// control did nothing; it is now repointed to edacp_scan_all_taxonomies.
	register_setting( 'edac_settings', 'edacp_scan_all_taxonomies', 'edac_sanitize_pro_taxonomy_terms' );
	register_setting( 'edac_settings', 'edacp_simplified_summary_heading', 'edac_sanitize_pro_summary_heading' );
}

/**
 * Render the text for the general section
 */
function edac_general_cb() {
	echo '<p>';

	esc_html_e( 'Configure the types of content that should be checked for accessibility issues.', 'accessibility-checker' );

	if ( ! edac_is_pro() ) {
		printf(
			/* translators: %1$s: link to the "Accessibility Checker Pro" website. */
			' ' . esc_html__( 'More features and email support is available with %1$s.', 'accessibility-checker' ),
			'<a href="' . esc_url(
				edac_generate_link_type(
					[
						'utm_campaign' => 'settings-page',
						'utm_content'  => 'features-and-support',
					]
				)
			) . '" target="_blank" aria-label="' . esc_attr__( 'Accessibility Checker Pro (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Accessibility Checker Pro', 'accessibility-checker' ) . '</a>'
		);
	}

	echo '</p>';
}

/**
 * Render the copy used to explain the frontend highlighter section.
 *
 * @return void
 */
function edac_frontend_highlighter_section_cb() {
	echo '<p>';
	esc_html_e( 'Use the settings below to configure the frontend accessibility checker.', 'accessibility-checker' );
	echo '</p>';
}

/**
 * Render the text for the simplified summary section
 */
function edac_simplified_summary_cb() {
	printf(
		'<p>%1$s %2$s</p>',
		esc_html__( 'Web Content Accessibility Guidelines (WCAG) at the AAA level require any content with a reading level above 9th grade to have an alternative that is easier to read. Simplified summary text is added on the readability tab in the Accessibility Checker meta box on each post\'s or page\'s edit screen.', 'accessibility-checker' ),
		'<a href="' . esc_url(
			edac_generate_link_type(
				[
					'utm_campaign' => 'settings-page',
					'utm_content'  => 'features-and-support',
				],
				'help',
				[ 'help_id' => 3265 ]
			)
		) . '" target="_blank" aria-label="' . esc_attr__( 'Learn more about simplified summaries and readability requirements (opens in a new window)', 'accessibility-checker' ) . '">' . esc_html__( 'Learn more about simplified summaries and readability requirements.', 'accessibility-checker' ) . '</a>'
	);
}

/**
 * Render the text for the footer accessiblity statement section
 */
function edac_footer_accessibility_statement_cb() {
	echo '<p>';
	echo esc_html__( 'Are you thinking "Wow, this plugin is amazing" and is it helping you make your website more accessible? Share your efforts to make your website more accessible with your customers and let them know you\'re using Accessibility Checker to ensure all people can use your website. Add a small text-only link and statement in the footer of your website.', 'accessibility-checker' );
	echo '</p>';
}

/**
 * Render the text for the system settings section
 */
function edac_system_cb() {
	echo '<p>';
	esc_html_e( 'Configure system-level settings for the Accessibility Checker plugin.', 'accessibility-checker' );
	echo '</p>';
}

/**
 * Render the dropdown input field for scan speed option.
 *
 * Note: this setting is purposefully using edacp as prefix for back compat reasons.
 */
function edac_full_site_scan_speed_cb() {

	$full_site_scan_speed = (int) get_option( 'edacp_full_site_scan_speed', 1000 );

	$speed_values = [
		'250'   => __( 'Fast', 'accessibility-checker' ),
		'1000'  => __( 'Normal', 'accessibility-checker' ),
		'5000'  => __( 'Slow', 'accessibility-checker' ),
		'30000' => __( 'Slowest', 'accessibility-checker' ),
	];

	?>
	<fieldset <?php echo ( edac_is_pro() ? '' : 'class="edac-setting--upsell"' ); ?>>
		<select
			name="edacp_full_site_scan_speed"
			id="edacp_full_site_scan_speed"
			aria-describedby="edac_scan_speed_desc"
			<?php disabled( ! edac_is_pro() ); ?>
		>
			<?php foreach ( $speed_values as $value => $label ) : ?>
				<option
					value="<?php echo esc_attr( $value ); ?>"
					<?php selected( $full_site_scan_speed, (int) $value ); ?>
				>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</fieldset>
	<p id="edac_scan_speed_desc" class="edac-description">
		<?php esc_html_e( 'Faster scans are more resource intensive and may place a high load on your website.', 'accessibility-checker' ); ?>
	</p>
	<?php
}

/**
 * Render the checkbox for enable archives scanning.
 *
 * Note: this setting is purposefully using edacp as prefix.
 *
 * @return void
 */
function edac_enable_archive_scanning_cb() {
	$enable_archives = get_option( 'edacp_enable_archive_scanning', false );
	?>
	<fieldset <?php echo edac_is_pro() ? '' : 'class="edac-setting--upsell"'; ?>>
		<label>
			<input
				type="checkbox"
				name="edacp_enable_archive_scanning"
				id="edacp_enable_archive_scanning"
				aria-describedby="edac_enable_archives_desc"
				value="1"
				<?php checked( $enable_archives, 1 ); ?>
				<?php disabled( ! edac_is_pro() ); ?>
			>
			<?php esc_html_e( 'Enable scanning of archive pages', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<p id="edac_enable_archives_desc" class="edac-description">
		<?php esc_html_e( 'Choose whether archive pages should be included in full site scans. By default, a sampling method is used to select taxonomy terms for archive data.', 'accessibility-checker' ); ?>
	</p>
	<?php
}

/**
 * Render the checkbox for scan all taxonomies
 *
 * Note: this setting is purposefully using edacp as prefix.
 *
 * @return void
 */
function edac_scan_all_taxonomy_terms_cb() {
	$scan_all_taxonomies = get_option( 'edacp_scan_all_taxonomies', false );
	$enable_archives     = get_option( 'edacp_enable_archive_scanning', false );
	?>
	<fieldset <?php echo ( edac_is_pro() ? '' : 'class="edac-setting--upsell"' ); ?>>
		<label>
			<input
				type="checkbox"
				name="edacp_scan_all_taxonomies"
				id="edacp_scan_all_taxonomies"
				value="1"
				<?php checked( $scan_all_taxonomies, 1 ); ?>
				<?php disabled( ! $enable_archives || ! edac_is_pro() ); ?>
			>
			<?php esc_html_e( 'Scan all taxonomy terms instead of just a sample', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<p class="edac-description">
		<?php esc_html_e( 'Check all taxonomy term archive pages instead of a representative sample. This requires archive page scanning to be enabled and may add a large number of URLs to the scan list.', 'accessibility-checker' ); ?>
	</p>
	<?php
}

/**
 * Sanitize the scan speed value before being saved to database.
 *
 * Can only be one of a few different numbers, representing milliseconds between polls.
 *
 * @param string $speed The scan speed value.
 * @return string
 */
function edac_sanitize_scan_speed( $speed ) {
	if ( in_array( $speed, [ '250', '1000', '5000', '30000' ], true ) ) {
		return $speed;
	}
	return '1000';
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_position_cb() {
	$position = get_option( 'edac_simplified_summary_position' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" id="<?php echo 'edac_simplified_summary_position'; ?>" value="before" <?php checked( $position, 'before' ); ?>>
				<?php esc_html_e( 'Before the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="after" <?php checked( $position, 'after' ); ?>>
				<?php esc_html_e( 'After the content', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_position'; ?>" value="none" <?php checked( $position, 'none' ); ?>>
				<?php esc_html_e( 'Insert manually', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<div id="ac-simplified-summary-option-code">
			<p><?php esc_html_e( 'Use this function to manually add the simplified summary to your theme within the loop.', 'accessibility-checker' ); ?></p>
			<kbd>edac_get_simplified_summary();</kbd>
			<p><?php esc_html_e( 'The function optionally accepts the post ID as a parameter.', 'accessibility-checker' ); ?><p>
			<kbd>edac_get_simplified_summary($post);</kbd>
		</div>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like simplified summaries to appear in relation to your content if filled in.', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Renders radio inputs for the frontend highlighter position option.
 *
 * @return void
 */
function edac_frontend_highlighter_position_cb() {
	$position = get_option( 'edac_frontend_highlighter_position', 'right' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="edac_frontend_highlighter_position" id="edac_frontend_highlighter_position" value="right" <?php checked( $position, 'right' ); ?>>
				<?php esc_html_e( 'Bottom Right Corner (default)', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="edac_frontend_highlighter_position" value="left" <?php checked( $position, 'left' ); ?>>
				<?php esc_html_e( 'Bottom Left Corner', 'accessibility-checker' ); ?>
			</label>
			<br>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Set where you would like the frontend accessibility checker to appear on the page.', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $position Position value.
 *
 * @return string
 */
function edac_sanitize_simplified_summary_position( $position ) {
	if ( in_array( $position, [ 'before', 'after', 'none' ], true ) ) {
		return $position;
	}
}

/**
 * Sanitize the frontend highlighter position value before being saved to database.
 *
 * @param string $position the position to save. Can only be 'right' or 'left'.
 *
 * @return string
 */
function edac_sanitize_frontend_highlighter_position( string $position ): string {
	if ( in_array( $position, [ 'right', 'left' ], true ) ) {
		return $position;
	}
	return 'right';
}

/**
 * Render the radio input field for position option
 */
function edac_simplified_summary_prompt_cb() {
	$prompt = get_option( 'edac_simplified_summary_prompt' );
	?>
		<fieldset>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" id="<?php echo 'edac_simplified_summary_prompt'; ?>" value="when required" <?php checked( $prompt, 'when required' ); ?>>
				<?php esc_html_e( 'When Required', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="always" <?php checked( $prompt, 'always' ); ?>>
				<?php esc_html_e( 'Always', 'accessibility-checker' ); ?>
			</label>
			<br>
			<label>
				<input type="radio" name="<?php echo 'edac_simplified_summary_prompt'; ?>" value="none" <?php checked( $prompt, 'none' ); ?>>
				<?php esc_html_e( 'Never', 'accessibility-checker' ); ?>
			</label>
		</fieldset>
		<p class="edac-description"><?php echo esc_html__( 'Should Accessibility Checker only ask for a simplified summary when the reading level of your post or page is above 9th grade, always ask for it regardless of reading level, or never ask for it regardless of reading level?', 'accessibility-checker' ); ?></p>
	<?php
}

/**
 * Sanitize the text position value before being saved to database
 *
 * @param array $prompt The text.
 *
 * @return string
 */
function edac_sanitize_simplified_summary_prompt( $prompt ) {
	if ( in_array( $prompt, [ 'when required', 'always', 'none' ], true ) ) {
		return $prompt;
	}
}

/**
 * Render the checkbox input field for post_types option
 */
function edac_post_types_cb() {

	$selected_post_types = get_option( 'edac_post_types' ) ? get_option( 'edac_post_types' ) : [];
	$post_types          = edac_post_types();
	$custom_post_types   = edac_custom_post_types();
	$all_post_types      = ( is_array( $post_types ) && is_array( $custom_post_types ) ) ? array_merge( $post_types, $custom_post_types ) : [];
	?>
		<fieldset>
			<?php
			if ( $all_post_types ) {
				$position = 0;
				foreach ( $all_post_types as $post_type ) {
					$disabled        = in_array( $post_type, $post_types, true ) ? '' : 'disabled';
					$post_type_label = edac_get_post_type_label( $post_type );
					$field_id        = ( 0 === $position ) ? 'edac_post_types' : "edac_post_types_{$post_type}";
					++$position;
					?>
					<label>
						<input type="checkbox" name="<?php echo 'edac_post_types[]'; ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $post_type ); ?>"
																<?php
																checked( in_array( $post_type, $selected_post_types, true ), 1 );
																echo esc_attr( $disabled );
																?>
						>
						<?php echo esc_html( $post_type_label ); ?>
					</label>
					<br>
					<?php
				}
			}
			?>
		</fieldset>
		<?php if ( defined( 'EDAC_KEY_VALID' ) && false === EDAC_KEY_VALID ) { ?>
			<p class="edac-description">
				<?php
				echo esc_html__( 'To check content other than posts and pages, please ', 'accessibility-checker' );
				?>
				<a href="<?php edac_link_wrapper( 'https://my.equalizedigital.com/', 'settings-page' ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'upgrade to pro', 'accessibility-checker' ); ?></a>
				<?php esc_html_e( ' (opens in a new window)', 'accessibility-checker' ); ?>
			</p>
		<?php } else { ?>
			<p class="edac-description">
				<?php
				esc_html_e( 'Choose which post types should be checked during a scan. Please note, removing a previously selected post type will remove its scanned information and any custom dismissed warnings that have been setup.', 'accessibility-checker' );
				?>
			</p>
			<?php
		}
}

/**
 * Sanitize the post type value before being saved to database
 *
 * @param array $selected_post_types Post types to sanitize.
 * @return array
 */
function edac_sanitize_post_types( $selected_post_types ) {

	$post_types = edac_post_types();

	if ( $selected_post_types ) {
		foreach ( $selected_post_types as $key => $post_type ) {
			if ( ! in_array( $post_type, $post_types, true ) ) {
				unset( $selected_post_types[ $key ] );
			}
		}
	}

	// get unselected post types.
	$unselected_post_types = $post_types;
	if ( $selected_post_types ) {
		$unselected_post_types = array_diff( $post_types, $selected_post_types );
	}

	// delete unselected post type issues.
	if ( $unselected_post_types ) {
		foreach ( $unselected_post_types as $unselected_post_type ) {
			Purge_Post_Data::delete_cpt_posts( $unselected_post_type );
		}
	}

	// clear cached stats if selected posts types change.
	$prev = array_values( array_unique( Settings::get_scannable_post_types() ) );
	$next = array_values( array_unique( (array) $selected_post_types ) );
	sort( $prev );
	sort( $next );
	if ( $prev !== $next ) {
		$scan_stats = new Scans_Stats();
		$scan_stats->clear_cache();

		// EDACP\Scans is the old namespace, kept for back compat but should be removed after a few releases.
		if ( class_exists( '\EDACP\Scans' ) || class_exists( '\EqualizeDigital\AccessibilityCheckerPro\Admin\Scans' ) ) {
			delete_option( 'edacp_fullscan_completed_at' );
		}
	}

	return $selected_post_types;
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_add_footer_accessibility_statement_cb() {

	$option = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="edac_add_footer_accessibility_statement" id="edac_add_footer_accessibility_statement" value="1" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Add Footer Accessibility Statement', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Render the checkbox input field for add footer accessibility statement option
 */
function edac_include_accessibility_statement_link_cb() {

	$option   = get_option( 'edac_include_accessibility_statement_link' ) ? get_option( 'edac_include_accessibility_statement_link' ) : false;
	$disabled = get_option( 'edac_add_footer_accessibility_statement' ) ? get_option( 'edac_add_footer_accessibility_statement' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="<?php echo 'edac_include_accessibility_statement_link'; ?>" id="edac_include_accessibility_statement_link" value="<?php echo '1'; ?>"
													<?php
													checked( $option, 1 );
													disabled( $disabled, false );
													?>
			>
			<?php esc_html_e( 'Include Link to Accessibility Policy', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Render the select field for accessibility policy page option
 */
function edac_accessibility_policy_page_cb() {

	$policy_page = get_option( 'edac_accessibility_policy_page' );
	?>

	<input style="width: 100%;" type="text" name="edac_accessibility_policy_page" id="edac_accessibility_policy_page" value="<?php echo esc_attr( $policy_page ); ?>">

	<?php
}

/**
 * Sanitize accessibility policy page values before being saved to database
 *
 * @param string $page Page to sanitize.
 * @return string
 */
function edac_sanitize_accessibility_policy_page( $page ) {
	if ( $page ) {
		return esc_url( $page );
	}
}

/**
 * Render the accessibility statement preview
 */
function edac_accessibility_statement_preview_cb() {
	echo wp_kses_post(
		( new Accessibility_Statement() )->get_accessibility_statement()
	);
}

/**
 * Render the checkbox input field for delete data option
 */
function edac_delete_data_cb() {

	$option = get_option( 'edac_delete_data' ) ? get_option( 'edac_delete_data' ) : false;

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="edac_delete_data" id="edac_delete_data" value="1" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Delete all Accessibility Checker data when the plugin is uninstalled.', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}


/**
 * Render the checkbox input field for toggling metabox visibility in the block editor.
 */
function edac_show_metabox_in_block_editor_cb() {

	$option = get_option( 'edac_show_metabox_in_block_editor', 1 );

	?>
	<fieldset>
		<label>
			<input type="checkbox" name="edac_show_metabox_in_block_editor" id="edac_show_metabox_in_block_editor" value="1" <?php checked( $option, 1 ); ?>>
			<?php esc_html_e( 'Show Accessibility Checker metabox in the Block Editor', 'accessibility-checker' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Wrapper sanitizers for pro settings that preserve existing values when pro is disabled
 *
 * @param mixed $input The input value.
 * @return mixed The existing option value
 */
function edac_sanitize_pro_scan_speed( $input ) {
	if ( edac_is_pro() ) {
		return edac_sanitize_scan_speed( $input );
	}
	return get_option( 'edacp_full_site_scan_speed', '1000' );
}

/**
 * Wrapper for sanitizing pro checkbox settings
 *
 * @param mixed  $input The input value.
 * @param string $option_name The option name being sanitized.
 * @return mixed The existing option value
 */
function edac_sanitize_pro_checkbox( $input, $option_name ) {
	if ( edac_is_pro() ) {
		/**
		 * Filter to run before saving a pro checkbox setting.
		 *
		 * @since 1.31.0
		 *
		 * @param string $option_name The option name being saved.
		 * @param mixed  $input The input value.
		 * @return void
		 */
		do_action( 'edac_pro_setting_saving_checkbox', $option_name, $input );
		return edac_sanitize_checkbox( $input );
	}
	return get_option( $option_name, 0 );
}

/**
 * Wrapper sanitizers for pro checkbox settings that preserve existing values when pro is disabled
 *
 * @param mixed $input The input value.
 * @return mixed The existing option value
 */
function edac_sanitize_pro_archive_scanning( $input ) {
	return edac_sanitize_pro_checkbox( $input, 'edacp_enable_archive_scanning' );
}

/**
 * Wrapper sanitizers for pro checkbox settings that preserve existing values when pro is disabled
 *
 * @param mixed $input The input value.
 * @return mixed The existing option value
 */
function edac_sanitize_pro_taxonomy_terms( $input ) {
	return edac_sanitize_pro_checkbox( $input, 'edacp_scan_all_taxonomies' );
}

/**
 * Wrapper sanitizers for pro summary heading setting that preserve existing values when pro is disabled
 *
 * @param string $input The input value.
 * @return string The existing option value
 */
function edac_sanitize_pro_summary_heading( $input ) {
	if ( edac_is_pro() ) {
		return sanitize_text_field( $input );
	}
	return get_option( 'edacp_simplified_summary_heading', esc_html__( 'Simplified Summary', 'accessibility-checker' ) );
}

/**
 * Sanitize checkbox values before being saved to database
 *
 * These are passed in as strings, but we will save them as integers.
 *
 * @since 1.11.0
 *
 * @param string|int $input Input to sanitize.
 * @return int either 1 for checked or 0 for unchecked
 */
function edac_sanitize_checkbox( $input ) {
	return ( isset( $input ) && ( '1' === $input || 1 === $input ) ) ? 1 : 0;
}

/**
 * Render the input field for simplified summary heading.
 *
 * @return void
 */
function edac_simplified_summary_heading_cb() {
	// phpcs:ignore Universal.Operators.DisallowShortTernary.Found -- ternary is more readable here.
	$simplified_summary_heading = get_option( 'edacp_simplified_summary_heading' ) ?: __( 'Simplified Summary', 'accessibility-checker' );
	?>
	<input
		<?php echo edac_is_pro() ? '' : 'class="edac-setting--upsell"'; ?>
		type="text"
		name="edacp_simplified_summary_heading"
		id="edacp_simplified_summary_heading"
		value="<?php echo esc_attr( $simplified_summary_heading ); ?>"
		<?php disabled( ! edac_is_pro() ); ?>
	>
	<?php
}
