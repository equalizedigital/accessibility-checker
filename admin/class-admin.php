<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\Pages\Settings_Page;
use EDAC\Admin\Pages\Welcome_Page;
use EDAC\Admin\Settings\Footer_Accessibility_Statement_Section;
use EDAC\Admin\Settings\General_Section;
use EDAC\Admin\Settings\Simplified_Summary_Section;
use EDAC\Admin\SiteHealth\Information;

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

		$admin_notices = new Admin_Notices();
		$admin_notices->init_hooks();

		$widgets = new Widgets();
		$widgets->init_hooks();

		$site_health_info = new Information();
		$site_health_info->init_hooks();

		$this->init_ajax();
		$this->init_pages();
		$this->init_sections();
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

	/**
	 * Initializes admin pages by instantiating each page class and invoking their hook registration methods.
	 *
	 * Make sure page loading order is preserved.
	 *
	 * @return void
	 */
	public function init_pages() {
		$pages = array(
			new Welcome_Page(),
			new Settings_Page(),
		);

		foreach ( $pages as $page ) {
			$page->init_hooks();
		}
	}

	/**
	 * Initializes settings sections by instantiating each section class and invoking their hook registration methods.
	 *
	 * Similar to `init_pages`, this method manages the settings sections of the plugin. Each section
	 * is responsible for adding itself and its fields to the WordPress settings API. The `init_hooks` method
	 * of each section class is called to handle the registration of hooks related to settings sections,
	 * ensuring that sections and their fields are properly set up in the admin.
	 *
	 * @return void
	 */
	public function init_sections() {
		$sections = array(
			new General_Section(),
			new Simplified_Summary_Section(),
			new Footer_Accessibility_Statement_Section(),
		);

		foreach ( $sections as $section ) {
			$section->init_hooks();
		}
	}
}
