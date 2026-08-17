<?php
/**
 * Admin page for assigning Accessibility Checker capabilities to roles and users.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Permissions settings tab: a role picker whose selected role can
 * be granted the Accessibility Checker capabilities it qualifies for. Saving
 * writes the edac_capability_role_map option, whose option hooks (registered by
 * SyncCapability) then sync the capabilities onto the selected roles.
 *
 * @since 1.xx.x
 */
class PermissionsPage implements PageInterface {

	const PAGE_TAB_SLUG = 'permissions';

	/**
	 * The option holding the capability => roles matrix.
	 *
	 * @var string
	 */
	const ROLE_MAP_OPTION = 'edac_capability_role_map';

	/**
	 * The admin-post action used to persist the matrix.
	 *
	 * @var string
	 */
	const SAVE_ACTION = 'edac_save_permissions';

	/**
	 * The capability required to view and edit this page.
	 *
	 * Deliberately hard-coded to manage_options rather than using the
	 * constructor's injected (filterable, via edac_filter_settings_capability)
	 * capability: this page grants OTHER roles capabilities, including the
	 * site-wide/global dismiss oversteps, so a site that lowers the general
	 * settings capability (e.g. to let editors into Settings) must not also
	 * let those editors grant themselves those oversteps. Every other tab is
	 * fine using the filterable capability; this one is not.
	 *
	 * @var string
	 */
	private const MANAGE_CAPABILITY = 'manage_options';

	/**
	 * Constructor.
	 *
	 * Accepts $settings_capability for interface compliance with the other
	 * settings tabs (PageInterface), but intentionally does not use it - see
	 * the note on self::MANAGE_CAPABILITY.
	 *
	 * @param string $settings_capability Unused; see self::MANAGE_CAPABILITY.
	 */
	public function __construct( $settings_capability ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Required by PageInterface; see self::MANAGE_CAPABILITY.
	}

	/**
	 * Wire the settings tab and its assets. Called on admin_menu, so this only
	 * covers requests that render the settings screen.
	 */
	public function add_page() {
		add_filter( 'edac_filter_settings_tab_items', [ $this, 'add_permissions_tab' ] );
		add_action( 'edac_settings_tab_content', [ $this, 'add_permissions_tab_content' ], 12, 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Wire the admin-post save handler. This must be registered on every request
	 * (admin-post.php does not build the admin menu), not just when the settings
	 * screen renders.
	 */
	public function register_request_handlers() {
		add_action( 'admin_post_' . self::SAVE_ACTION, [ $this, 'handle_save' ] );
	}

	/**
	 * Add the Permissions tab to the settings navigation.
	 *
	 * @param array $settings_tab_items Array of tab items.
	 * @return array
	 */
	public function add_permissions_tab( $settings_tab_items ) {
		$settings_tab_items[] = [
			'slug'       => self::PAGE_TAB_SLUG,
			'label'      => __( 'Permissions', 'accessibility-checker' ),
			'order'      => 3,
			'capability' => self::MANAGE_CAPABILITY,
		];

		return $settings_tab_items;
	}

	/**
	 * Render the Permissions tab content when its tab is active.
	 *
	 * @param string $tab Name of the current tab.
	 * @return void
	 */
	public function add_permissions_tab_content( $tab ) {
		if ( self::PAGE_TAB_SLUG !== $tab ) {
			return;
		}

		if ( ! current_user_can( self::MANAGE_CAPABILITY ) ) {
			return;
		}

		include EDAC_PLUGIN_DIR . 'partials/admin-page/permissions-page.php';
	}

	/**
	 * Render the page.
	 *
	 * Required by PageInterface. Guarded the same way as the tab callback: this
	 * partial exposes the whole capability role map (and the grant UI that
	 * writes it), so any caller reaching it must hold self::MANAGE_CAPABILITY.
	 */
	public function render_page() {
		if ( ! current_user_can( self::MANAGE_CAPABILITY ) ) {
			return;
		}

		include EDAC_PLUGIN_DIR . 'partials/admin-page/permissions-page.php';
	}

	/**
	 * Enqueue the role-picker script on the Permissions tab only.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only checks that only decide whether to enqueue an asset.
		$is_settings_page = isset( $_GET['page'] ) && 'accessibility_checker_settings' === sanitize_key( wp_unslash( $_GET['page'] ) );
		$is_permissions   = isset( $_GET['tab'] ) && self::PAGE_TAB_SLUG === sanitize_key( wp_unslash( $_GET['tab'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $is_settings_page || ! $is_permissions ) {
			return;
		}

		if ( ! current_user_can( self::MANAGE_CAPABILITY ) ) {
			return;
		}

		wp_enqueue_style(
			'edac-permissions',
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'admin/css/permissions.css',
			[],
			EDAC_VERSION
		);

		wp_enqueue_script(
			'edac-permissions',
			plugin_dir_url( EDAC_PLUGIN_FILE ) . 'admin/js/permissions.js',
			[],
			EDAC_VERSION,
			true
		);

		wp_localize_script(
			'edac-permissions',
			'edacPermissions',
			[
				'matrix'  => $this->build_matrix_payload(),
				'strings' => [
					'selectRoleTitle'   => __( 'Select a role', 'accessibility-checker' ),
					'selectRole'        => __( 'Select a role to see the capabilities it can be granted.', 'accessibility-checker' ),
					'noCaps'            => __( 'No capabilities are available.', 'accessibility-checker' ),
					// translators: %s is the selected user role name.
					'permissionsLoaded' => __( 'Permission settings loaded for %s.', 'accessibility-checker' ),
				],
			]
		);
	}

	/**
	 * A stable fingerprint of a role map, used as an optimistic-concurrency
	 * check on save (not for any security purpose).
	 *
	 * The Permissions page bakes the full current role map into hidden inputs
	 * at render time; handle_save() then overwrites the option with whatever
	 * the browser submits. Two admins editing around the same time - the
	 * second one's page still reflecting the pre-first-save state - would
	 * otherwise have the second save silently discard the first admin's
	 * changes. This fingerprint is rendered into the form at page-load time
	 * and re-checked against the option's current value at save time, so a
	 * save whose starting snapshot is no longer current is rejected instead
	 * of silently overwriting.
	 *
	 * @param array $role_map The role map to fingerprint.
	 * @return string
	 */
	private function role_map_revision( array $role_map ): string {
		ksort( $role_map );
		foreach ( $role_map as &$roles ) {
			$roles = array_values( (array) $roles );
			sort( $roles );
		}
		unset( $roles );

		return md5( (string) wp_json_encode( $role_map ) );
	}

	/**
	 * Build the role/capability eligibility matrix consumed by the picker JS.
	 *
	 * For every editable role and every capability it reports whether the
	 * capability can be assigned to that role (the role's live capabilities meet
	 * the capability's floor and the capability is not license-locked) and, when
	 * it cannot, a human-readable reason. The current assignments themselves are
	 * rendered as hidden form inputs by the partial, not carried here.
	 *
	 * @return array{roles:array,caps:array,state:array}
	 */
	private function build_matrix_payload(): array {
		$metadata = edac_capability_metadata();
		$roles    = edac_assignable_roles();

		$payload = [
			'roles' => [],
			'caps'  => [],
			'state' => [],
		];

		foreach ( $roles as $role_slug => $role ) {
			$payload['roles'][] = [
				'slug' => $role_slug,
				'name' => translate_user_role( $role['name'] ),
			];
		}

		foreach ( $metadata as $slug => $meta ) {
			$payload['caps'][] = [
				'slug'        => $slug,
				'label'       => $meta['label'],
				'description' => $meta['description'],
				'group'       => '' !== $meta['group'] ? $meta['group'] : __( 'Other', 'accessibility-checker' ),
				'owner'       => $meta['owner'],
			];
		}

		foreach ( $roles as $role_slug => $role ) {
			foreach ( $metadata as $slug => $meta ) {
				$editable = edac_capability_is_editable( $slug, $meta );
				$meets    = edac_role_meets_floor( $role_slug, $meta['floor'] );

				if ( ! $editable ) {
					$reason = __( 'Requires a valid license.', 'accessibility-checker' );
				} elseif ( ! $meets ) {
					$reason = edac_floor_requirement_label( $meta['floor'] );
				} else {
					$reason = '';
				}

				$payload['state'][ $role_slug ][ $slug ] = [
					'enabled' => $editable && $meets,
					'reason'  => $reason,
				];
			}
		}

		return $payload;
	}

	/**
	 * Persist the submitted role matrix, then redirect back.
	 *
	 * Only capabilities that are editable in the UI (their owning plugin is
	 * active, and the capability is not a locked upsell) are read from the
	 * request; locked capabilities keep their existing assignments untouched.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( self::MANAGE_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Accessibility Checker permissions.', 'accessibility-checker' ) );
		}

		check_admin_referer( self::SAVE_ACTION );

		// Nonce verified above via check_admin_referer(). Each element of this
		// array is sanitized by edac_sanitize_capability_role_map() (sanitize_key
		// per role, floor + editable-role re-validation per capability).
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_roles    = isset( $_POST['edac_role_map'] ) ? (array) wp_unslash( $_POST['edac_role_map'] ) : [];
		$posted_revision = isset( $_POST['edac_role_map_revision'] ) ? sanitize_text_field( wp_unslash( $_POST['edac_role_map_revision'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$current_role_map = (array) get_option( self::ROLE_MAP_OPTION, [] );

		// Optimistic concurrency: the submitted form started from a snapshot of
		// the role map at page-load time (see role_map_revision() and the
		// partial's hidden field). If the stored option no longer matches that
		// snapshot, someone else saved in between - reject rather than silently
		// overwrite whatever they just changed with this now-stale submission.
		if ( $posted_revision !== $this->role_map_revision( $current_role_map ) ) {
			$redirect = add_query_arg(
				[
					'page'    => 'accessibility_checker_settings',
					'tab'     => self::PAGE_TAB_SLUG,
					'updated' => 'conflict',
				],
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect );
			exit;
		}

		// Validate through the shared sanitizer so the UI save and the settings
		// importer apply identical rules. It preserves entries for locked/inactive
		// capabilities, narrows each editable capability to assignable roles that
		// meet its floor, and drops anything that fails - so a role that no longer
		// qualifies (or was never allowed) is never granted. Assignments for an
		// add-on that is currently inactive are left untouched (only cleared on
		// uninstall).
		$role_map = edac_sanitize_capability_role_map( $posted_roles );

		update_option( self::ROLE_MAP_OPTION, $role_map );

		$redirect = add_query_arg(
			[
				'page'    => 'accessibility_checker_settings',
				'tab'     => self::PAGE_TAB_SLUG,
				'updated' => 'true',
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
