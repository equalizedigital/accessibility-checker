<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Admin;

/**
 * Main plugin functionality class.
 */
class Plugin {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( \is_admin() ) {
			$admin = new Admin();
			$admin->init();
		} else {
			$this->init();
		}

		// The REST api must load if admin or not.
		$rest_api = new REST_Api();
		$rest_api->init_hooks();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init() {

		$accessibility_statement = new Accessibility_Statement();
		$accessibility_statement->init_hooks();

		$simplified_summary = new Simplified_Summary();
		$simplified_summary->init_hooks();

		$lazyload_filter = new Lazyload_Filter();
		$lazyload_filter->init_hooks();
	}
}
