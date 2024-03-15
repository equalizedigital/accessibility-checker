<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\SiteHealth\Information;
use EDAC\Admin\Purge_Post_Data;

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

		$update_database = new Update_Database();
		$update_database->init_hooks();

		add_action( 'admin_enqueue_scripts', array( 'EDAC\Admin\Enqueue_Admin', 'enqueue' ) );
		add_action( 'wp_trash_post', array( Purge_Post_Data::class, 'delete_post' ) );

		$admin_notices = new Admin_Notices();
		$admin_notices->init_hooks();

		$widgets = new Widgets();
		$widgets->init_hooks();

		$site_health_info = new Information();
		$site_health_info->init_hooks();

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
