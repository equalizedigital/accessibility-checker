<?php
/**
 * Class file for managing the welcome page in the WordPress admin area for the Accessibility Checker plugin.
 *
 * @package EDAC\Admin\Pages
 */

namespace EDAC\Admin\Pages;

/**
 * Class Welcome_Page
 *
 * Handles the creation and display of the welcome page in the WordPress admin area for the Accessibility Checker plugin.
 */
class Welcome_Page {

	/**
	 * Initializes WordPress hooks for the admin menu.
	 *
	 * This method sets up the hook necessary to add the welcome page to the WordPress admin menu.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Adds a menu page to the WordPress admin area.
	 *
	 * This method uses the add_menu_page function to add a new top-level menu page
	 * for the Accessibility Checker plugin in the WordPress dashboard.
	 *
	 * @return void
	 */
	public function add_page() {
		add_menu_page(
			__( 'Welcome to Accessibility Checker', 'accessibility-checker' ), // Page title.
			__( 'Accessibility Checker', 'accessibility-checker' ), // Menu title.
			'read', // Capability required to see this page.
			'accessibility_checker', // Menu slug.
			array( $this, 'display' ), // Function that outputs the content of the page.
			'dashicons-universal-access-alt' // Icon URL.
		);
	}

	/**
	 * Displays the welcome page content.
	 *
	 * This method includes the partial file that contains the HTML content of the welcome page.
	 * It's called when the welcome page menu item is clicked in the WordPress admin area.
	 *
	 * @return void
	 */
	public function display() {
		include_once plugin_dir_path( __DIR__ ) . '../partials/welcome-page.php';
	}
}
