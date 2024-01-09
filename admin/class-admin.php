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
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function init() {
		
		$admin_notices = new Admin_Notices();
		$admin_notices->init_hooks();

		$widgets = new Widgets();
		$widgets->init_hooks();

		Options::boot();
		
		
		$this->init_ajax();
	}

	/**
	 * Initialize ajax.
	 *
	 * @return void
	 */
	private function init_ajax() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		$ajax = new Ajax();
		$ajax->init_hooks();

		$frontend_highlight = new Frontend_Highlight();
		$frontend_highlight->init_hooks();
	}
}
