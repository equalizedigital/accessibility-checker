<?php
/**
 * Class file for Admin Toolbar functionality.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

/**
 * Class that handles admin toolbar menu items for Accessibility Checker.
 *
 * @since 1.27.0
 */
class Admin_Toolbar {

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

		// Add parent menu item.
		$wp_admin_bar->add_menu(
			[
				'id'    => 'accessibility-checker',
				'title' => '<span class="ab-icon dashicons dashicons-universal-access-alt"></span>' . __( 'Accessibility Checker', 'accessibility-checker' ),
				'href'  => admin_url( 'admin.php?page=accessibility_checker' ),
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

		// Add each menu item to the toolbar.
		foreach ( $menu_items as $item ) {
			$wp_admin_bar->add_menu( $item );
		}
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

		// Add Fixes submenu item.
		$menu_items[] = [
			'id'     => 'accessibility-checker-fixes',
			'parent' => 'accessibility-checker',
			'title'  => __( 'Fixes', 'accessibility-checker' ),
			'href'   => admin_url( 'admin.php?page=accessibility_checker_settings&tab=fixes' ),
		];

		// Add Get Pro submenu item (only show if pro is not installed or license is not valid).
		if ( ! defined( 'EDACP_VERSION' ) || ! EDAC_KEY_VALID ) {
			$pro_link = function_exists( 'edac_generate_link_type' ) 
				? edac_generate_link_type( [ 'utm-content' => 'admin-toolbar' ] )
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
}
