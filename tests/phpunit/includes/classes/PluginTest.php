<?php
/**
 * Tests for the plugin bootstrap class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Plugin;
use EqualizeDigital\AccessibilityChecker\Fixes\FixesManager;

/**
 * Tests for the wiring \EDAC\Inc\Plugin performs when it is constructed.
 *
 * The suite bootstrap loads the plugin through `muplugins_loaded`, so its own
 * instance of this class is built before coverage collection starts. Every
 * assertion below is made against an instance created inside the test, and for
 * the callbacks that are not bound to that instance the bootstrap's
 * registration is removed first — otherwise the assertion would pass whether
 * the code under test ran or not.
 *
 * @covers \EDAC\Inc\Plugin
 */
class PluginTest extends WP_UnitTestCase {

	/**
	 * Start every test on the front-end screen.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_current_screen_context( 'front' );
	}

	/**
	 * Put the front-end screen back after each test.
	 */
	public function tearDown(): void {
		$this->set_current_screen_context( 'front' );

		parent::tearDown();
	}

	/**
	 * Set the current screen without firing the screen hooks.
	 *
	 * The current screen decides which branch of the constructor runs, because
	 * is_admin() reads it. WP_Screen::get() is used instead of
	 * set_current_screen() so no screen hooks fire as a side effect.
	 *
	 * @param string $screen Screen to set as the current one.
	 */
	private function set_current_screen_context( string $screen ): void {
		$GLOBALS['current_screen'] = WP_Screen::get( $screen );
	}

	/**
	 * On the front end the constructor registers the front-end enqueue hooks and
	 * defers the fixes manager and the screen reader only user meta.
	 */
	public function test_constructor_registers_the_front_end_wiring() {
		remove_action( 'wp_enqueue_scripts', [ 'EDAC\Inc\Enqueue_Frontend', 'enqueue' ] );
		remove_action( 'send_headers', [ 'EDAC\Inc\Enqueue_Frontend', 'maybe_send_scan_isolation_header' ] );

		$plugin = new Plugin();

		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', [ 'EDAC\Inc\Enqueue_Frontend', 'enqueue' ] ) );
		$this->assertSame( 10, has_action( 'send_headers', [ 'EDAC\Inc\Enqueue_Frontend', 'maybe_send_scan_isolation_header' ] ) );
		$this->assertSame( 20, has_action( 'plugins_loaded', [ $plugin, 'init_fixes_manager' ] ) );
		$this->assertSame( 10, has_action( 'init', [ $plugin, 'register_sr_only_user_meta' ] ) );
	}

	/**
	 * Inside wp-admin the constructor takes the admin branch instead: it loads the
	 * admin screen, and leaves the front-end enqueue hooks alone.
	 */
	public function test_constructor_registers_the_admin_wiring_inside_wp_admin() {
		$this->set_current_screen_context( 'dashboard' );
		remove_action( 'admin_enqueue_scripts', [ 'EDAC\Admin\Enqueue_Admin', 'enqueue' ] );
		remove_action( 'wp_enqueue_scripts', [ 'EDAC\Inc\Enqueue_Frontend', 'enqueue' ] );
		remove_action( 'send_headers', [ 'EDAC\Inc\Enqueue_Frontend', 'maybe_send_scan_isolation_header' ] );

		$plugin = new Plugin();

		$this->assertSame( 10, has_action( 'admin_enqueue_scripts', [ 'EDAC\Admin\Enqueue_Admin', 'enqueue' ] ) );
		$this->assertSame( 10, has_action( 'init', [ $plugin, 'register_sr_only_user_meta' ] ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', [ 'EDAC\Inc\Enqueue_Frontend', 'enqueue' ] ) );
		$this->assertFalse( has_action( 'send_headers', [ 'EDAC\Inc\Enqueue_Frontend', 'maybe_send_scan_isolation_header' ] ) );
	}

	/**
	 * The screen reader only preference is registered as a REST visible boolean
	 * user meta, which is the contract the editor app reads it through.
	 */
	public function test_register_sr_only_user_meta_registers_a_rest_visible_boolean_meta() {
		unregister_meta_key( 'user', 'show_sr_text_in_editor' );

		$this->assertArrayNotHasKey( 'show_sr_text_in_editor', get_registered_meta_keys( 'user' ) );

		$plugin = new Plugin();
		$plugin->register_sr_only_user_meta();

		$meta = get_registered_meta_keys( 'user' );

		$this->assertArrayHasKey( 'show_sr_text_in_editor', $meta );
		$this->assertSame( 'boolean', $meta['show_sr_text_in_editor']['type'] );
		$this->assertTrue( $meta['show_sr_text_in_editor']['single'] );
		$this->assertTrue( $meta['show_sr_text_in_editor']['show_in_rest'] );
	}

	/**
	 * Running the deferred callback registers a fix on the manager and exposes the
	 * fixes over the REST API.
	 */
	public function test_init_fixes_manager_registers_the_fixes_and_their_rest_routes() {
		$manager = FixesManager::get_instance();

		remove_action( 'rest_api_init', [ $manager, 'register_rest_routes' ] );

		$this->assertFalse( has_action( 'rest_api_init', [ $manager, 'register_rest_routes' ] ) );

		$plugin = new Plugin();
		$plugin->init_fixes_manager();

		$this->assertSame( 10, has_action( 'rest_api_init', [ $manager, 'register_rest_routes' ] ) );
		$this->assertNotNull( $manager->get_fix( 'skip_link' ) );
	}
}
