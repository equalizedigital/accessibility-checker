<?php
/**
 * Class file for the Accessibility Checker plugin.
 *
 * @package Accessibility_Checker
 */

namespace EDAC\Inc;

use EDAC\Admin\Admin;
use EDAC\Admin\Meta_Boxes;
use EDAC\Admin\Orphaned_Issues_Cleanup;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage;
use EqualizeDigital\AccessibilityChecker\MyDot\Connector;
use EqualizeDigital\AccessibilityChecker\WPCLI\BootstrapCLI;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin functionality class.
 */
class Plugin {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( \is_admin() ) {
			$meta_boxes = new Meta_Boxes();
			$admin      = new Admin( $meta_boxes );
			$admin->init();
		} else {
			$this->init();
		}

		// The REST api must load if admin or not.
		$rest_api = new REST_Api();
		$rest_api->init_hooks();

		// Initialize the admin toolbar.
		$admin_toolbar = new Admin_Toolbar();
		$admin_toolbar->init();

		$cleanup = new Orphaned_Issues_Cleanup();
		$cleanup->init_hooks();

		$this->register_fixes_manager();
		$this->register_sr_only_meta_hooks();

		$accessibility_reports = new AccessibilityReportsPage( 'manage_options' );
		$accessibility_reports->add_page();

		$connector = new Connector();
		$connector->init();

		// When WP CLI is enabled, load the CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action(
				'init',
				function () {
					$cli = new BootstrapCLI();
					$cli->register();
				}
			);
		}
	}

	/**
	 * Initialize.
	 *
	 * @return void
	 */
	private function init() {

		add_action( 'wp_enqueue_scripts', [ 'EDAC\Inc\Enqueue_Frontend', 'enqueue' ] );

		$accessibility_statement = new Accessibility_Statement();
		$accessibility_statement->init_hooks();

		$simplified_summary = new Simplified_Summary();
		$simplified_summary->init_hooks();

		$lazyload_filter = new Lazyload_Filter();
		$lazyload_filter->init_hooks();
	}

	/**
	 * Register hooks for the screen reader only user meta.
	 *
	 * @return void
	 */
	public function register_sr_only_meta_hooks(): void {
		add_action( 'init', [ $this, 'register_sr_only_user_meta' ] );
	}

	/**
	 * Register user meta for the screen reader text always-show preference.
	 *
	 * @return void
	 */
	public function register_sr_only_user_meta(): void {
		register_meta(
			'user',
			'show_sr_text_in_editor',
			[
				'type'         => 'boolean',
				'single'       => true,
				'show_in_rest' => true,
			]
		);
	}

	/**
	 * Register the FixesManager.
	 *
	 * @return void
	 */
	public function register_fixes_manager() {
		add_action( 'plugins_loaded', [ $this, 'init_fixes_manager' ], 20 );
	}

	/**
	 * Init the FixesManager.
	 *
	 * This is done on the plugins_loaded hook with a priority of 20 to ensure that fixes that
	 * rely on running early, like on init or before init, can be hooked in and ready to go.
	 * Fixes should be registered to the manager using the the plugins_loaded hook with a
	 * priority of less than 20.
	 *
	 * @return void
	 */
	public function init_fixes_manager() {
		$fixes_manager = FixesManager::get_instance();
		$fixes_manager->register_fixes();
		add_action( 'rest_api_init', [ $fixes_manager, 'register_rest_routes' ] );
	}
}
