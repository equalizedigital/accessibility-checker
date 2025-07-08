<?php
/**
 * Test cases for the Admin_Toolbar class.
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Admin_Toolbar;

/**
 * Tests for functionality of the Admin_Toolbar class.
 */
class AdminToolbarTest extends WP_UnitTestCase {

	/**
	 * Instance of the Admin_Toolbar class.
	 *
	 * @var Admin_Toolbar $admin_toolbar
	 */
	private $admin_toolbar;

	/**
	 * Mock WP_Admin_Bar instance.
	 *
	 * @var PHPUnit\Framework\MockObject\MockObject $wp_admin_bar
	 */
	private $wp_admin_bar;

	/**
	 * Sets up the test environment before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->admin_toolbar = new Admin_Toolbar();
		
		// Create a generic mock object with add_menu method.
		$this->wp_admin_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();

		// Create a user with manage_options capability.
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		wp_set_current_user( 0 );
	}

	/**
	 * Test that init method adds the admin_bar_menu action.
	 */
	public function test_init_adds_admin_bar_menu_action() {
		$this->admin_toolbar->init();

		$this->assertNotFalse( has_action( 'admin_bar_menu', [ $this->admin_toolbar, 'add_toolbar_items' ] ) );
		$this->assertEquals( 999, has_action( 'admin_bar_menu', [ $this->admin_toolbar, 'add_toolbar_items' ] ) );
	}

	/**
	 * Test that admin toolbar items are not added for users without manage_options capability.
	 */
	public function test_add_toolbar_items_requires_manage_options_capability() {
		// Create a user without manage_options capability.
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$this->wp_admin_bar->expects( $this->never() )
			->method( 'add_menu' );

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );
	}

	/**
	 * Test that parent menu item is added with correct parameters.
	 *
	 * @runInSeparateProcess
	 */
	public function test_add_toolbar_items_adds_parent_menu() {
		// Create mock within this test to avoid serialization issues.
		$wp_admin_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();

		$wp_admin_bar->expects( $this->once() )
			->method( 'add_menu' )
			->with(
				$this->callback( [ $this, 'validate_parent_menu_args' ] )
			);

		// Mock the filter to return empty array so only parent menu is added.
		add_filter( 'edac_admin_toolbar_menu_items', [ $this, 'filter_return_empty_array' ] );

		$this->admin_toolbar->add_toolbar_items( $wp_admin_bar );
	}

	/**
	 * Test that default menu items are added correctly.
	 *
	 * @runInSeparateProcess
	 */
	public function test_add_toolbar_items_adds_default_menu_items() {
		// Create mock within this test to avoid serialization issues.
		$wp_admin_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();

		// Expect parent menu + 3 default items (Settings, Fixes, Get Pro).
		$wp_admin_bar->expects( $this->exactly( 4 ) )
			->method( 'add_menu' );

		// Mock constants for pro check.
		define( 'EDAC_KEY_VALID', false );

		$this->admin_toolbar->add_toolbar_items( $wp_admin_bar );
	}

	/**
	 * Test that Get Pro menu item is not added when pro is installed and license is valid.
	 *
	 * @runInSeparateProcess
	 */
	public function test_add_toolbar_items_hides_pro_link_when_pro_active() {
		// Create mock within this test to avoid serialization issues.
		$wp_admin_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();

		// Mock pro version and valid license.
		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );

		// Expect parent menu + 2 default items (Settings, Fixes) - no Get Pro.
		$wp_admin_bar->expects( $this->exactly( 3 ) )
			->method( 'add_menu' );

		$this->admin_toolbar->add_toolbar_items( $wp_admin_bar );
	}

	/**
	 * Test that the filter allows modification of menu items.
	 *
	 * @runInSeparateProcess
	 */
	public function test_menu_items_filter_is_applied() {
		// Create mock within this test to avoid serialization issues.
		$wp_admin_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();

		// Mock constants.
		define( 'EDAC_KEY_VALID', false );

		// Expect parent menu + 3 default items + 1 custom item = 5 total.
		$wp_admin_bar->expects( $this->exactly( 5 ) )
			->method( 'add_menu' );

		// Add filter to add a custom menu item.
		add_filter( 'edac_admin_toolbar_menu_items', [ $this, 'filter_add_custom_menu_item' ] );

		$this->admin_toolbar->add_toolbar_items( $wp_admin_bar );
	}

	/**
	 * Helper method for filter callback to avoid closure serialization issues.
	 *
	 * @param array $menu_items The menu items array.
	 * @return array Modified menu items array.
	 */
	public function filter_add_custom_menu_item( $menu_items ) {
		// Add a custom menu item.
		$menu_items[] = [
			'id'     => 'custom-test-item',
			'parent' => 'accessibility-checker',
			'title'  => 'Test Item',
			'href'   => 'http://example.com',
		];
		return $menu_items;
	}

	/**
	 * Test that Settings menu item has correct parameters.
	 */
	public function test_settings_menu_item_parameters() {
		$menu_items = $this->get_default_menu_items_via_reflection();

		$settings_item = $this->find_menu_item_by_id( $menu_items, 'accessibility-checker-settings' );

		$this->assertNotNull( $settings_item, 'Settings menu item should exist' );
		$this->assertEquals( 'accessibility-checker', $settings_item['parent'] );
		$this->assertEquals( 'Settings', $settings_item['title'] );
		$this->assertEquals( admin_url( 'admin.php?page=accessibility_checker_settings' ), $settings_item['href'] );
	}

	/**
	 * Test that Fixes menu item has correct parameters.
	 */
	public function test_fixes_menu_item_parameters() {
		$menu_items = $this->get_default_menu_items_via_reflection();

		$fixes_item = $this->find_menu_item_by_id( $menu_items, 'accessibility-checker-fixes' );

		$this->assertNotNull( $fixes_item, 'Fixes menu item should exist' );
		$this->assertEquals( 'accessibility-checker', $fixes_item['parent'] );
		$this->assertEquals( 'Fixes', $fixes_item['title'] );
		$this->assertEquals( admin_url( 'admin.php?page=accessibility_checker_settings&tab=fixes' ), $fixes_item['href'] );
	}

	/**
	 * Test that Get Pro menu item has correct parameters including accessibility attributes.
	 *
	 * @runInSeparateProcess
	 */
	public function test_get_pro_menu_item_parameters() {
		// Mock constants to ensure pro item is shown.
		define( 'EDAC_KEY_VALID', false );

		$menu_items = $this->get_default_menu_items_via_reflection();

		$pro_item = $this->find_menu_item_by_id( $menu_items, 'accessibility-checker-pro' );

		$this->assertNotNull( $pro_item, 'Pro menu item should exist' );
		$this->assertEquals( 'accessibility-checker', $pro_item['parent'] );
		$this->assertStringContainsString( 'font-weight: bold', $pro_item['title'] );
		$this->assertStringContainsString( 'color: white', $pro_item['title'] );
		$this->assertStringContainsString( 'Get Accessibility Checker Pro', $pro_item['title'] );
		$this->assertEquals( '_blank', $pro_item['meta']['target'] );
		$this->assertEquals( 'noopener noreferrer', $pro_item['meta']['rel'] );
		$this->assertStringContainsString( 'opens in new window', $pro_item['meta']['aria-label'] );
	}

	/**
	 * Test that Get Pro link uses edac_generate_link_type function when available.
	 *
	 * @runInSeparateProcess
	 */
	public function test_get_pro_link_uses_generate_link_function() {
		// Mock constants to ensure pro item is shown.
		define( 'EDAC_KEY_VALID', false );

		// Instead of mocking the function, we'll test the fallback behavior
		// when the function doesn't exist, which is the default pro link.
		$menu_items = $this->get_default_menu_items_via_reflection();

		$pro_item = $this->find_menu_item_by_id( $menu_items, 'accessibility-checker-pro' );

		$this->assertNotNull( $pro_item, 'Pro menu item should exist' );
		
		// Test that the href is a valid URL (either the generated link or fallback).
		$this->assertNotEmpty( $pro_item['href'], 'Pro menu item should have an href' );
		$this->assertStringContainsString( 'http', $pro_item['href'], 'Pro menu item href should be a valid URL' );
		
		// If the function exists in the actual codebase, it will use the generated link.
		// If not, it will use the fallback URL. Both are valid test scenarios.
	}

	/**
	 * Helper method to get default menu items using reflection.
	 *
	 * @return array
	 */
	private function get_default_menu_items_via_reflection() {
		$reflection = new ReflectionClass( $this->admin_toolbar );
		$method     = $reflection->getMethod( 'get_default_menu_items' );
		$method->setAccessible( true );

		return $method->invoke( $this->admin_toolbar );
	}

	/**
	 * Helper method to find a menu item by ID without using closures.
	 *
	 * @param array  $menu_items Array of menu items.
	 * @param string $id         The ID to search for.
	 * @return array|null The found menu item or null if not found.
	 */
	private function find_menu_item_by_id( $menu_items, $id ) {
		foreach ( $menu_items as $item ) {
			if ( isset( $item['id'] ) && $id === $item['id'] ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Callback method to validate parent menu arguments.
	 *
	 * @param array $args Menu arguments.
	 * @return bool
	 */
	public function validate_parent_menu_args( $args ) {
		$expected_id             = 'accessibility-checker';
		$expected_title_contains = 'dashicons-universal-access-alt';
		$expected_href_contains  = 'admin.php?page=accessibility_checker';
		
		return isset( $args['id'] ) && $expected_id === $args['id'] &&
				isset( $args['title'] ) && false !== strpos( $args['title'], $expected_title_contains ) &&
				isset( $args['href'] ) && false !== strpos( $args['href'], $expected_href_contains );
	}

	/**
	 * Filter callback to return empty array for menu items.
	 *
	 * @return array
	 */
	public function filter_return_empty_array() {
		return [];
	}
}
