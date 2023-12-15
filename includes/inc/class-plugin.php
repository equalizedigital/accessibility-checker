<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Admin;

/**
 * AccessibilityNewWindowWarnings Class.
 */
class Plugin {

	/**
	 * EDAC constructor.
	 */
	public function __construct() {
		if ( \is_admin() ) {
			new Admin();
		} else {
			$this->init();
		}
	}

	/**
	 * Init.
	 */
	public function init() {
        $accessibility_statement = new \EDAC\Inc\Accessibility_Statement();
        $accessibility_statement->init_hooks();
	}

}
