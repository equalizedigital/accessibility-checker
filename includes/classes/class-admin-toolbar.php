<?php
/**
 * Class file for Admin Toolbar functionality.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EqualizeDigital\AccessibilityChecker\Admin\MenuOrder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that handles admin toolbar menu items for Accessibility Checker.
 *
 * @since 1.27.0
 */
class Admin_Toolbar {

	/**
	 * User meta key that stores a user's admin bar display preference.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const USER_META_KEY = 'edac_admin_toolbar_display';

	/**
	 * Display mode: icon and text (default).
	 *
	 * @since x.x.x
	 * @var string
	 */
	const MODE_FULL = 'full';

	/**
	 * Display mode: icon only.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const MODE_ICON = 'icon';

	/**
	 * Display mode: hidden.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const MODE_HIDDEN = 'hidden';

	/**
	 * Constructor
	 *
	 * @since 1.27.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the admin toolbar functionality.
	 *
	 * @since 1.27.0
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_items' ], 999 );

		// Per-user display preference on the profile screen.
		add_action( 'show_user_profile', [ $this, 'render_profile_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'render_profile_fields' ] );
		add_action( 'personal_options_update', [ $this, 'save_profile_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_profile_fields' ] );
	}

	/**
	 * Get the valid display modes.
	 *
	 * @since x.x.x
	 * @return string[] Display mode slugs.
	 */
	private function get_valid_modes(): array {
		return [ self::MODE_FULL, self::MODE_ICON, self::MODE_HIDDEN ];
	}

	/**
	 * Whether the toolbar item is allowed to show for a user.
	 *
	 * @since x.x.x
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_item_enabled( int $user_id ): bool {
		/**
		 * Filters whether the Accessibility Checker admin toolbar item is available.
		 *
		 * Return false to remove the menu from the admin bar entirely. This overrides
		 * the user's profile preference, and the preference is hidden from their profile.
		 * Returning true leaves it to the user's own display preference.
		 *
		 * @since x.x.x
		 *
		 * @param bool $enabled Whether the toolbar item is enabled. Default true.
		 * @param int  $user_id ID of the user the admin bar is being built for.
		 */
		return (bool) apply_filters( 'edac_show_admin_toolbar_item', true, $user_id );
	}

	/**
	 * Get the display mode for a user.
	 *
	 * @since x.x.x
	 * @param int $user_id User ID.
	 * @return string One of the MODE_* constants.
	 */
	public function get_display_mode( int $user_id ): string {
		$mode = get_user_meta( $user_id, self::USER_META_KEY, true );
		if ( ! in_array( $mode, $this->get_valid_modes(), true ) ) {
			$mode = self::MODE_FULL;
		}

		/**
		 * Filters the display mode of the Accessibility Checker admin toolbar item.
		 *
		 * @since x.x.x
		 *
		 * @param string $mode    One of 'full' (icon and text), 'icon' (icon only) or 'hidden'.
		 * @param int    $user_id ID of the user the admin bar is being built for.
		 */
		$mode = apply_filters( 'edac_admin_toolbar_display_mode', $mode, $user_id );

		return in_array( $mode, $this->get_valid_modes(), true ) ? $mode : self::MODE_FULL;
	}

	/**
	 * Add Accessibility Checker items to the admin toolbar.
	 *
	 * @since 1.27.0
	 * @param \WP_Admin_Bar $wp_admin_bar The WP_Admin_Bar instance.
	 * @return void
	 */
	public function add_toolbar_items( $wp_admin_bar ): void {

		// Only show for users who can manage options.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $this->is_item_enabled( $user_id ) ) {
			return;
		}

		$mode = $this->get_display_mode( $user_id );
		if ( self::MODE_HIDDEN === $mode ) {
			return;
		}

		$label = __( 'Accessibility Checker', 'accessibility-checker' );
		$icon  = '<span class="ab-icon dashicons dashicons-universal-access-alt"></span>';
		$meta  = [];

		if ( self::MODE_ICON === $mode ) {
			// Keep an accessible name for screen readers and a tooltip for sighted users.
			$title         = $icon . '<span class="screen-reader-text">' . esc_html( $label ) . '</span>';
			$meta['title'] = $label;
		} else {
			$title = $icon . $label;
		}

		// Add parent menu item.
		$wp_admin_bar->add_menu(
			[
				'id'    => 'accessibility-checker',
				'title' => $title,
				'href'  => admin_url( 'admin.php?page=accessibility_checker' ),
				'meta'  => $meta,
			]
		);

		// Get default menu items.
		$menu_items = $this->get_default_menu_items();

		/**
		 * Filters the admin toolbar menu items for Accessibility Checker.
		 *
		 * Allows plugins to add, remove, or modify menu items in the
		 * Accessibility Checker admin toolbar dropdown.
		 *
		 * @since 1.27.0
		 *
		 * @param array $menu_items Array of menu item configurations. Each item should be
		 *                          an associative array with keys: id, parent, title, href,
		 *                          and optionally meta for additional attributes.
		 */
		$menu_items = apply_filters( 'edac_admin_toolbar_menu_items', $menu_items );

		// Follow the same order as the admin submenu.
		$menu_items = $this->sort_menu_items( $menu_items );

		// Add each menu item to the toolbar.
		foreach ( $menu_items as $item ) {
			unset( $item['menu_slug'] );
			$wp_admin_bar->add_menu( $item );
		}
	}

	/**
	 * Map of toolbar item IDs to the admin submenu slug they correspond to.
	 *
	 * Items not listed here can set their own `menu_slug` key. Anything with no
	 * slug is placed where the submenu order puts unlisted items.
	 *
	 * @since x.x.x
	 * @return array<string,string>
	 */
	private function get_menu_slug_map(): array {
		return [
			'accessibility-checker-issues-explorer'       => 'accessibility_checker_issues_explorer',
			'accessibility-checker-full-site-scan'        => 'accessibility_checker_full_site_scan',
			'accessibility-checker-audit-history'         => 'accessibility_checker_audit_history',
			'accessibility-checker-export-data'           => 'accessibility_checker_export_data',
			'accessibility-checker-archive-pages'         => 'edit.php?post_type=edac_virtual_item',
			'accessibility-checker-settings'              => 'accessibility_checker_settings',
			'accessibility-checker-settings-general'      => 'accessibility_checker_settings',
			'accessibility-checker-fixes'                 => 'accessibility_checker_settings',
			'accessibility-checker-permissions'           => 'accessibility_checker_settings',
			'accessibility-checker-accessibility-reports' => 'accessibility_checker_settings',
			'accessibility-checker-pro'                   => 'accessibility_checker_upgrade',
		];
	}

	/**
	 * Sort toolbar items into the shared admin menu order.
	 *
	 * @since x.x.x
	 * @param array $menu_items Toolbar item configurations.
	 * @return array
	 */
	private function sort_menu_items( array $menu_items ): array {
		$map = $this->get_menu_slug_map();

		return ( new MenuOrder() )->sort(
			$menu_items,
			static function ( $item ) use ( $map ) {
				if ( ! empty( $item['menu_slug'] ) ) {
					return (string) $item['menu_slug'];
				}
				$id = isset( $item['id'] ) ? (string) $item['id'] : '';
				return $map[ $id ] ?? '';
			}
		);
	}

	/**
	 * Get the default menu items for the admin toolbar.
	 *
	 * @since 1.27.0
	 * @return array Array of menu item configurations.
	 */
	private function get_default_menu_items(): array {
		$menu_items = [];

		// Add Settings submenu item.
		$menu_items[] = [
			'id'     => 'accessibility-checker-settings',
			'parent' => 'accessibility-checker',
			'title'  => __( 'Settings', 'accessibility-checker' ),
			'href'   => admin_url( 'admin.php?page=accessibility_checker_settings' ),
		];

		// Flyout for the settings tabs. These are defined here rather than read from
		// edac_filter_settings_tab_items because that filter is only populated on
		// admin_menu, which does not run on the frontend where the toolbar also shows.
		// Add-ons can add their own tabs through edac_admin_toolbar_menu_items, using
		// 'accessibility-checker-settings' as the parent. The parent item stays a link
		// to the General tab, and General is repeated as the first child so touch
		// devices, which open the flyout instead of following the parent link, can
		// still reach it.
		$menu_items[] = [
			'id'     => 'accessibility-checker-settings-general',
			'parent' => 'accessibility-checker-settings',
			'title'  => __( 'General', 'accessibility-checker' ),
			'href'   => admin_url( 'admin.php?page=accessibility_checker_settings' ),
		];

		$menu_items[] = [
			'id'     => 'accessibility-checker-fixes',
			'parent' => 'accessibility-checker-settings',
			'title'  => __( 'Fixes', 'accessibility-checker' ),
			'href'   => admin_url( 'admin.php?page=accessibility_checker_settings&tab=fixes' ),
		];

		// The Permissions tab always requires manage_options, matching PermissionsPage.
		if ( current_user_can( 'manage_options' ) ) {
			$menu_items[] = [
				'id'     => 'accessibility-checker-permissions',
				'parent' => 'accessibility-checker-settings',
				'title'  => __( 'Permissions', 'accessibility-checker' ),
				'href'   => admin_url( 'admin.php?page=accessibility_checker_settings&tab=permissions' ),
			];
		}

		// The Accessibility Reports tab uses the filterable settings capability, matching AccessibilityReportsPage.
		/** This filter is documented in includes/options-page.php */
		if ( current_user_can( apply_filters( 'edac_filter_settings_capability', 'manage_options' ) ) ) { // phpcs:ignore WordPress.WP.Capabilities.Undetermined -- Filterable settings capability.
			$menu_items[] = [
				'id'     => 'accessibility-checker-accessibility-reports',
				'parent' => 'accessibility-checker-settings',
				'title'  => __( 'Accessibility Reports', 'accessibility-checker' ),
				'href'   => admin_url( 'admin.php?page=accessibility_checker_settings&tab=accessibility-reports' ),
			];
		}

		// Add Get Pro submenu item (only show if pro is not installed or license is not valid).
		if ( ! defined( 'EDACP_VERSION' ) || ! ( defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) ) {
			$pro_link = function_exists( 'edac_generate_link_type' ) 
				? edac_generate_link_type( [ 'utm_content' => 'admin-toolbar' ] )
				: 'https://equalizedigital.com/accessibility-checker/pricing/';

			$menu_items[] = [
				'id'     => 'accessibility-checker-pro',
				'parent' => 'accessibility-checker',
				'title'  => '<span style="font-weight: bold; color: white;">' . __( 'Get Accessibility Checker Pro', 'accessibility-checker' ) . '</span>',
				'href'   => $pro_link,
				'meta'   => [
					'target'     => '_blank',
					'rel'        => 'noopener noreferrer',
					'aria-label' => __( 'Get Accessibility Checker Pro (opens in new window)', 'accessibility-checker' ),
				],
			];
		}

		return $menu_items;
	}

	/**
	 * Render the admin bar display preference on the user profile screen.
	 *
	 * @since x.x.x
	 * @param \WP_User $user The user whose profile is being edited.
	 * @return void
	 */
	public function render_profile_fields( $user ): void {
		if ( ! ( $user instanceof \WP_User ) || ! user_can( $user, 'manage_options' ) ) {
			return;
		}

		if ( ! $this->is_item_enabled( $user->ID ) ) {
			return;
		}

		$current = get_user_meta( $user->ID, self::USER_META_KEY, true );
		if ( ! in_array( $current, $this->get_valid_modes(), true ) ) {
			$current = self::MODE_FULL;
		}

		$options = [
			self::MODE_FULL   => __( 'Icon and text', 'accessibility-checker' ),
			self::MODE_ICON   => __( 'Icon only', 'accessibility-checker' ),
			self::MODE_HIDDEN => __( 'Hidden', 'accessibility-checker' ),
		];
		?>
		<h2><?php esc_html_e( 'Accessibility Checker', 'accessibility-checker' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Admin bar menu', 'accessibility-checker' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Admin bar menu', 'accessibility-checker' ); ?></span></legend>
						<?php foreach ( $options as $value => $label ) : ?>
							<label for="edac-admin-toolbar-display-<?php echo esc_attr( $value ); ?>">
								<input
									type="radio"
									name="<?php echo esc_attr( self::USER_META_KEY ); ?>"
									id="edac-admin-toolbar-display-<?php echo esc_attr( $value ); ?>"
									value="<?php echo esc_attr( $value ); ?>"
									<?php checked( $current, $value ); ?>
								/>
								<?php echo esc_html( $label ); ?>
							</label><br />
						<?php endforeach; ?>
						<p class="description">
							<?php esc_html_e( 'Quick links to Accessibility Checker settings and fixes in the top admin bar. Choose "Icon only" if your admin bar is crowded. Hiding the menu also hides any shortcuts added to it in the future.', 'accessibility-checker' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the admin bar display preference from the user profile screen.
	 *
	 * Core verifies the profile form nonce before these hooks run.
	 *
	 * @since x.x.x
	 * @param int $user_id ID of the user being saved.
	 * @return void
	 */
	public function save_profile_fields( $user_id ): void {
		$user_id = (int) $user_id;

		if ( ! current_user_can( 'edit_user', $user_id ) || ! user_can( $user_id, 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by core before this hook.
		if ( ! isset( $_POST[ self::USER_META_KEY ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified by core before this hook.
		$mode = sanitize_key( wp_unslash( $_POST[ self::USER_META_KEY ] ) );

		if ( ! in_array( $mode, $this->get_valid_modes(), true ) ) {
			return;
		}

		if ( self::MODE_FULL === $mode ) {
			delete_user_meta( $user_id, self::USER_META_KEY );
			return;
		}

		update_user_meta( $user_id, self::USER_META_KEY, $mode );
	}
}
