<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Admin;

use EDAC\Admin\SiteHealth\Information;
use EDAC\Admin\SiteHealth\Checks;
use EDAC\Admin\Purge_Post_Data;
use EDAC\Admin\Post_Save;
use EDAC\Admin\Dashboard_Glance;
use EqualizeDigital\AccessibilityChecker\Admin\Upgrade_Promotion;
use EqualizeDigital\AccessibilityChecker\Admin\Admin_Footer_Text;

/**
 * Admin handling class.
 */
class Admin {

	/**
	 * Meta boxes instance.
	 *
	 * @since 1.10.0
	 *
	 * @var Meta_Boxes
	 */
	private Meta_Boxes $meta_boxes;

	/**
	 * Class constructor for injecting dependencies.
	 *
	 * @param Meta_Boxes $meta_boxes Meta boxes instance.
	 */
	public function __construct( Meta_Boxes $meta_boxes ) {
		$this->meta_boxes = $meta_boxes;
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	public function init(): void {

		$update_database = new Update_Database();
		$update_database->init_hooks();

		add_action( 'admin_enqueue_scripts', [ 'EDAC\Admin\Enqueue_Admin', 'enqueue' ] );
		add_action( 'wp_trash_post', [ Purge_Post_Data::class, 'delete_post' ] );
		add_action( 'save_post', [ Post_Save::class, 'delete_issue_data_on_post_trashing' ], 10, 3 );

		$plugin_action_links = new Plugin_Action_Links();
		$plugin_action_links->init_hooks();

		$admin_notices = new Admin_Notices();
		$admin_notices->init_hooks();

		$widgets = new Widgets();
		$widgets->init_hooks();
		
		$dashboard_glance = new Dashboard_Glance();
		$dashboard_glance->init_hooks();

		$site_health_info = new Information();
		$site_health_info->init_hooks();

		$site_health_checks = new Checks();
		$site_health_checks->init_hooks();

		$upgrade_promotion = new Upgrade_Promotion();
		$upgrade_promotion->init();

		$plugin_row_meta = new Plugin_Row_Meta();
		$plugin_row_meta->init_hooks();

		$admin_footer_text = new Admin_Footer_Text();
		$admin_footer_text->init();

		$this->init_ajax();

		$this->meta_boxes->init_hooks();
	}

	/**
	 * Initialize ajax.
	 *
	 * @return void
	 */
	private function init_ajax(): void {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		$ajax = new Ajax();
		$ajax->init_hooks();

		$frontend_highlight = new Frontend_Highlight();
		$frontend_highlight->init_hooks();
	}
}
