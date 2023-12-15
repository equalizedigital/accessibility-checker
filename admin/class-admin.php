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
		$this->init();
		$this->init_ajax();
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init() {
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

		$ajax = new \EDAC\Admin\Frontend_Highlight();
		$ajax->init_hooks();
	}

}
