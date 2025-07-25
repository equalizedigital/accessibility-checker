<?php
/**
 * Class UpgradePromotionTest
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\Upgrade_Promotion;

/**
 * Upgrade Promotion test case.
 */
class UpgradePromotionTest extends WP_UnitTestCase {

	/**
	 * Instance of the Upgrade_Promotion class.
	 *
	 * @var Upgrade_Promotion $upgrade_promotion.
	 */
	private $upgrade_promotion;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->upgrade_promotion = new Upgrade_Promotion();
	}

	/**
	 * Tear down the test fixture.
	 */
	protected function tearDown(): void {
		// Remove any filters added during tests.
		remove_all_filters( 'edac_is_sale_time' );
		remove_all_filters( 'edac_filter_settings_capability' );
		
		// Note: Constants like EDACP_VERSION can't be undefined in PHP once defined.
		// In a real implementation, dependency injection would be used to avoid this testing issue.
		
		parent::tearDown();
	}

	/**
	 * Test that the init method exists.
	 */
	public function test_init_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'init' ),
			'Class does not have method init'
		);
	}

	/**
	 * Test that init method adds the admin_menu action.
	 */
	public function test_init_adds_admin_menu_action() {
		// Remove any existing actions first.
		remove_all_actions( 'admin_menu' );
		remove_all_actions( 'admin_head' );
		remove_all_actions( 'admin_init' );
		
		// Call init.
		$this->upgrade_promotion->init();
		
		// Check that the admin_menu action was added and get the priority.
		$priority = has_action( 'admin_menu', [ $this->upgrade_promotion, 'add_menu_item' ] );
		
		// has_action returns false if not found, or the priority (integer) if found.
		$this->assertNotFalse( $priority, 'admin_menu action was not added' );
		$this->assertIsInt( $priority, 'Priority should be an integer' );
		
		// Check priority is 999.
		$this->assertEquals(
			999,
			$priority,
			'admin_menu action priority is not 999'
		);

		// Check that admin_head action was added.
		$head_priority = has_action( 'admin_head', [ $this->upgrade_promotion, 'add_menu_styling' ] );
		$this->assertNotFalse( $head_priority, 'admin_head action was not added' );

		// Check that admin_init action was added.
		$init_priority = has_action( 'admin_init', [ $this->upgrade_promotion, 'maybe_handle_redirect' ] );
		$this->assertNotFalse( $init_priority, 'admin_init action was not added' );
	}

	/**
	 * Test that add_menu_item method exists.
	 */
	public function test_add_menu_item_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'add_menu_item' ),
			'Class does not have method add_menu_item'
		);
	}

	/**
	 * Test that maybe_handle_redirect method exists.
	 */
	public function test_maybe_handle_redirect_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'maybe_handle_redirect' ),
			'Class does not have method maybe_handle_redirect'
		);
	}

	/**
	 * Test that dummy_page_callback method exists.
	 */
	public function test_dummy_page_callback_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'dummy_page_callback' ),
			'Class does not have method dummy_page_callback'
		);
	}

	/**
	 * Test that allow_redirect_host method exists.
	 */
	public function test_allow_redirect_host_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'allow_redirect_host' ),
			'Class does not have method allow_redirect_host'
		);
	}

	/**
	 * Test that allow_redirect_host correctly adds domain to hosts.
	 */
	public function test_allow_redirect_host_adds_domain() {
		$initial_hosts  = [ 'wordpress.org', 'example.com' ];
		$expected_hosts = array_merge( $initial_hosts, [ 'equalizedigital.com' ] );
		
		$result = $this->upgrade_promotion->allow_redirect_host( $initial_hosts );
		
		$this->assertEquals( $expected_hosts, $result, 'allow_redirect_host should add equalizedigital.com to hosts array' );
		$this->assertContains( 'equalizedigital.com', $result, 'allow_redirect_host should add equalizedigital.com to hosts array' );
	}

	/**
	 * Test that add_menu_styling method exists.
	 */
	public function test_add_menu_styling_method_exists() {
		$this->assertTrue(
			method_exists( $this->upgrade_promotion, 'add_menu_styling' ),
			'Class does not have method add_menu_styling'
		);
	}

	/**
	 * Test that add_menu_item doesn't add menu for users without proper capability.
	 */
	public function test_add_menu_item_checks_user_capability() {
		// Create a user without manage_options capability.
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );
		
		// Mock the global submenu to check if anything was added.
		global $submenu;
		$original_submenu = $submenu;
		$submenu          = [];
		
		// Call add_menu_item.
		$this->upgrade_promotion->add_menu_item();
		
		// Check that no submenu was added.
		$this->assertEmpty( $submenu, 'Menu was added for user without proper capability' );
		
		// Restore original submenu.
		$submenu = $original_submenu;
	}

	/**
	 * Test that add_menu_item adds menu for users with proper capability when pro is not active.
	 */
	public function test_add_menu_item_adds_menu_for_admin_user() {
		// Create an admin user.
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		
		// Ensure pro version is not active (no constants defined).
		// Constants can't be undefined, so we assume clean state.
		
		// Mock the global submenu.
		global $submenu;
		$original_submenu = $submenu;
		$submenu          = [];
		
		// Call add_menu_item.
		$this->upgrade_promotion->add_menu_item();
		
		// Check that submenu was added to accessibility_checker.
		$this->assertNotEmpty( $submenu, 'No submenu was added' );
		
		// Restore original submenu.
		$submenu = $original_submenu;
	}

	/**
	 * Test the sale time filter functionality.
	 */
	public function test_sale_time_filter() {
		// Test default (no sale).
		$reflection = new ReflectionClass( $this->upgrade_promotion );
		$method     = $reflection->getMethod( 'is_sale_time' );
		$method->setAccessible( true );
		
		$this->assertFalse( $method->invoke( $this->upgrade_promotion ), 'Default sale time should be false' );
		
		// Test with filter returning true.
		add_filter( 'edac_is_sale_time', '__return_true' );
		$this->assertTrue( $method->invoke( $this->upgrade_promotion ), 'Sale time filter should return true' );
		
		// Test with filter returning false.
		remove_filter( 'edac_is_sale_time', '__return_true' );
		add_filter( 'edac_is_sale_time', '__return_false' );
		$this->assertFalse( $method->invoke( $this->upgrade_promotion ), 'Sale time filter should return false' );
	}

	/**
	 * Test menu label changes based on sale status.
	 */
	public function test_menu_label_changes_with_sale_status() {
		// Create an admin user.
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		
		global $submenu;
		$original_submenu = $submenu;
		
		// Test normal state (no sale).
		add_filter( 'edac_is_sale_time', '__return_false' );
		$submenu = [];
		
		$this->upgrade_promotion->add_menu_item();
		
		// Check that the normal menu label is used.
		$this->assertNotEmpty( $submenu, 'No submenu was added for normal state' );
		$this->assertArrayHasKey( 'accessibility_checker', $submenu, 'accessibility_checker submenu not found' );
		
		$menu_items   = $submenu['accessibility_checker'];
		$upgrade_item = null;
		foreach ( $menu_items as $item ) {
			if ( isset( $item[2] ) && 'accessibility_checker_upgrade' === $item[2] ) {
				$upgrade_item = $item;
				break;
			}
		}
		
		$this->assertNotNull( $upgrade_item, 'Upgrade menu item not found in normal state' );
		$this->assertEquals( 'Upgrade to Pro', $upgrade_item[0], 'Normal state menu label is incorrect' );
		
		// Test sale state.
		remove_filter( 'edac_is_sale_time', '__return_false' );
		add_filter( 'edac_is_sale_time', '__return_true' );
		$submenu = [];
		
		$this->upgrade_promotion->add_menu_item();
		
		// Check that the sale menu label is used.
		$this->assertNotEmpty( $submenu, 'No submenu was added for sale state' );
		$this->assertArrayHasKey( 'accessibility_checker', $submenu, 'accessibility_checker submenu not found in sale state' );
		
		$menu_items   = $submenu['accessibility_checker'];
		$upgrade_item = null;
		foreach ( $menu_items as $item ) {
			if ( isset( $item[2] ) && 'accessibility_checker_upgrade' === $item[2] ) {
				$upgrade_item = $item;
				break;
			}
		}
		
		$this->assertNotNull( $upgrade_item, 'Upgrade menu item not found in sale state' );
		$this->assertEquals( 'Upgrade Sale Now', $upgrade_item[0], 'Sale state menu label is incorrect' );
		
		// Restore original submenu.
		$submenu = $original_submenu;
	}

	/**
	 * Test pro version detection.
	 */
	public function test_pro_version_detection() {
		$reflection = new ReflectionClass( $this->upgrade_promotion );
		$method     = $reflection->getMethod( 'is_pro_active' );
		$method->setAccessible( true );
		
		// Test when pro is not active (default state).
		$this->assertFalse( $method->invoke( $this->upgrade_promotion ), 'Pro should not be active by default' );
		
		// Note: We can't easily test the true case since constants can't be undefined once defined,
		// and defining them here would affect other tests. In a real scenario, you might use
		// dependency injection or make the constants configurable for testing.
	}

	/**
	 * Test that styling is only added on accessibility checker pages.
	 */
	public function test_styling_only_on_accessibility_checker_pages() {
		// Create an admin user for proper capability check.
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		
		// Test on a non-accessibility checker page.
		set_current_screen( 'dashboard' );
		ob_start();
		$this->upgrade_promotion->add_menu_styling();
		$output = ob_get_clean();
		$this->assertEmpty( $output, 'Styling should not be added on non-plugin pages' );
		
		// Test on an accessibility checker page.
		set_current_screen( 'toplevel_page_accessibility_checker' );
		ob_start();
		$this->upgrade_promotion->add_menu_styling();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'Styling should be added on plugin pages' );
		$this->assertStringContainsString( '<style', $output, 'Output should contain CSS styles' );
		$this->assertStringContainsString( 'accessibility_checker_upgrade', $output, 'CSS should target upgrade menu item' );
		$this->assertStringContainsString( '#f3cd1e', $output, 'CSS should contain the expected yellow color' );
	}

	/**
	 * Test that required WordPress functions are available.
	 */
	public function test_wordpress_functions_available() {
		$required_functions = [
			'add_action',
			'current_user_can',
			'add_submenu_page',
			'wp_safe_redirect',
			'get_current_screen',
			'apply_filters',
		];
		
		foreach ( $required_functions as $function ) {
			$this->assertTrue(
				function_exists( $function ),
				"Required WordPress function {$function} is not available"
			);
		}
	}

	/**
	 * Test class namespace and structure.
	 */
	public function test_class_namespace_and_structure() {
		$reflection = new ReflectionClass( $this->upgrade_promotion );
		
		// Test namespace.
		$this->assertEquals(
			'EqualizeDigital\AccessibilityChecker\Admin',
			$reflection->getNamespaceName(),
			'Class should be in the correct namespace'
		);
		
		// Test that all expected methods are public/private as intended.
		$public_methods  = [ 'init', 'add_menu_item', 'add_menu_styling', 'allow_redirect_host', 'maybe_handle_redirect', 'dummy_page_callback' ];
		$private_methods = [ 'is_pro_active', 'is_sale_time' ];
		
		foreach ( $public_methods as $method_name ) {
			$method = $reflection->getMethod( $method_name );
			$this->assertTrue(
				$method->isPublic(),
				"Method {$method_name} should be public"
			);
		}
		
		foreach ( $private_methods as $method_name ) {
			$method = $reflection->getMethod( $method_name );
			$this->assertTrue(
				$method->isPrivate(),
				"Method {$method_name} should be private"
			);
		}
	}
}
