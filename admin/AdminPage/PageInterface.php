<?php
/**
 * Interface for managing admin pages.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin\AdminPage;

interface PageInterface {

	/**
	 * Constructor.
	 *
	 * @param string $settings_capability The capability required to access the settings page.
	 */
	public function __construct( $settings_capability );

	/**
	 * Add the page to the admin menu and register any settings.
	 */
	public function add_page();

	/**
	 * Render the page.
	 */
	public function render_page();
}
