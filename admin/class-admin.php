<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

/**
 * Admin handling class.
 */
class Admin {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->init_hooks();

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $ajax = new \EDAC\Admin\Ajax();
            $ajax->init_hooks();
        }
	}

	/**
	 * Sets up hooks for admin actions.
	 */
	public function init_hooks() {
	}

}
