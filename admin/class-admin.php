<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;
use EDAC;

/**
 * Admin handling class.
 */
class Admin {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->init();
		$this->init_ajax();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init() {
		$admin_notices = new \EDAC\Admin\Admin_Notices();
		$admin_notices->init_hooks();

		$widgets = new \EDAC\Admin\Widgets();
		$widgets->init_hooks();

		new EDAC\REST_Api();
	}

	/**
	 * Initialize ajax.
	 *
	 * @return void
	 */
	private function init_ajax() {
		if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
			return;
		}

		$ajax = new \EDAC\Admin\Ajax();
		$ajax->init_hooks();

		$frontend_highlight = new \EDAC\Admin\Frontend_Highlight();
		$frontend_highlight->init_hooks();
	}
}
