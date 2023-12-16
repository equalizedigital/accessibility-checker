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
			new Admin();
		} else {
			$this->init();
		}
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init() {
        $accessibility_statement = new \EDAC\Inc\Accessibility_Statement();
        $accessibility_statement->init_hooks();

		$simplified_summary = new \EDAC\Inc\Simplified_Summary();
        $simplified_summary->init_hooks();
	}
}
