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
 * @since 1.48.0
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
	 * @var string
	 */
	private $settings_capability;

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability ) {
		$this->settings_capability = $settings_capability;
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
			'capability' => $this->settings_capability,
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

		if ( ! current_user_can( $this->settings_capability ) ) {
			return;
		}

		include EDAC_PLUGIN_DIR . 'partials/admin-page/permissions-page.php';
	}

	/**
	 * Render the page.
	 */
	public function render_page() {
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
					'selectRole' => __( 'Select a role to see the capabilities it can be granted.', 'accessibility-checker' ),
					'noCaps'     => __( 'No capabilities are available.', 'accessibility-checker' ),
				],
			]
		);
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
		if ( ! current_user_can( $this->settings_capability ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Accessibility Checker permissions.', 'accessibility-checker' ) );
		}

		check_admin_referer( self::SAVE_ACTION );

		$metadata       = edac_capability_metadata();
		$editable_roles = array_keys( edac_assignable_roles() );

		// Nonce verified above via check_admin_referer(). Each element of this
		// array is sanitized in the loop below (sanitize_key).
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$posted_roles = isset( $_POST['edac_role_map'] ) ? (array) wp_unslash( $_POST['edac_role_map'] ) : [];
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$role_map = get_option( self::ROLE_MAP_OPTION, [] );
		$role_map = is_array( $role_map ) ? $role_map : [];

		foreach ( $metadata as $slug => $meta ) {
			// Locked (upsell) rows are display-only; leave their stored value alone.
			if ( ! edac_capability_is_editable( $slug, $meta ) ) {
				continue;
			}

			$roles_for_cap = isset( $posted_roles[ $slug ] ) ? array_map( 'sanitize_key', (array) $posted_roles[ $slug ] ) : [];
			$roles_for_cap = array_values( array_intersect( $roles_for_cap, $editable_roles ) );

			// Re-validate each role against the capability's floor, so a role that
			// no longer qualifies (e.g. it lost edit_others_posts after the setting
			// was saved) is dropped rather than silently kept.
			$roles_for_cap = array_values(
				array_filter(
					$roles_for_cap,
					function ( $role_slug ) use ( $meta ) {
						return edac_role_meets_floor( $role_slug, $meta['floor'] );
					}
				)
			);

			if ( $roles_for_cap ) {
				$role_map[ $slug ] = $roles_for_cap;
			} else {
				unset( $role_map[ $slug ] );
			}
		}

		// Assignments for capabilities whose add-on is currently inactive are left
		// untouched (not pruned), so deactivating an add-on and saving this page
		// does not forget its configuration; re-activating restores it. These
		// entries are only cleared when the free plugin is uninstalled.
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
