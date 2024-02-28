<?php
/**
 * Class file for managing the settings page of the Accessibility Checker plugin.
 *
 * @package EDAC\Admin\Pages
 */

namespace EDAC\Admin\Pages;

use EDAC\Admin\Helpers;

/**
 * Handles the settings page for the Accessibility Checker plugin.
 */
class Settings_Page {

	/**
	 * Initialize class hooks.
	 */
	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Adds a submenu page under the Accessibility Checker main menu.
	 */
	public function add_page() {
		// Check if the current user has the capability to view this page.
		if ( ! Helpers::edac_user_can_ignore() ) {
			return;
		}

		$settings_capability = apply_filters( 'edac_filter_settings_capability', 'manage_options' );

		add_submenu_page(
			'accessibility_checker',
			__( 'Accessibility Checker Settings', 'accessibility-checker' ),
			__( 'Settings', 'accessibility-checker' ),
			$settings_capability,
			'accessibility_checker_settings',
			array( $this, 'display' )
		);
	}

	/**
	 * Renders the settings page for the plugin.
	 */
	public function display() {
		include_once plugin_dir_path( __DIR__ ) . '../partials/settings-page.php';
	}
}
