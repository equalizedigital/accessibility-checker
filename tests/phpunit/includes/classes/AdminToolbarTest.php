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
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
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

		$this->assertTrue( has_action( 'admin_bar_menu', [ $this->admin_toolbar, 'add_toolbar_items' ] ) );
		$this->assertEquals( 999, has_action( 'admin_bar_menu', [ $this->admin_toolbar, 'add_toolbar_items' ] ) );
	}

	/**
	 * Test that admin toolbar items are not added for users without manage_options capability.
	 */
	public function test_add_toolbar_items_requires_manage_options_capability() {
		// Create a user without manage_options capability.
		$user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$this->wp_admin_bar->expects( $this->never() )
			->method( 'add_menu' );

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );
	}

	/**
	 * Test that parent menu item is added with correct parameters.
	 */
	public function test_add_toolbar_items_adds_parent_menu() {
		$this->wp_admin_bar->expects( $this->once() )
			->method( 'add_menu' )
			->with(
				$this->callback(
					function ( $args ) {
						return 'accessibility-checker' === $args['id'] &&
						false !== strpos( $args['title'], 'dashicons-universal-access-alt' ) &&
						false !== strpos( $args['title'], 'Accessibility Checker' ) &&
						admin_url( 'admin.php?page=accessibility_checker' ) === $args['href'];
					} 
				) 
			);

		// Mock the filter to return empty array so only parent menu is added.
		add_filter(
			'edac_admin_toolbar_menu_items',
			function () {
				return [];
			} 
		);

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );
	}

	/**
	 * Test that default menu items are added correctly.
	 */
	public function test_add_toolbar_items_adds_default_menu_items() {
		// Expect parent menu + 3 default items (Settings, Fixes, Get Pro).
		$this->wp_admin_bar->expects( $this->exactly( 4 ) )
			->method( 'add_menu' );

		// Mock constants for pro check.
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', false );
		}

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );
	}

	/**
	 * Test that Get Pro menu item is not added when pro is installed and license is valid.
	 */
	public function test_add_toolbar_items_hides_pro_link_when_pro_active() {
		// Mock pro version and valid license.
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', true );
		}

		// Expect parent menu + 2 default items (Settings, Fixes) - no Get Pro.
		$this->wp_admin_bar->expects( $this->exactly( 3 ) )
			->method( 'add_menu' );

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );
	}

	/**
	 * Test that the filter allows modification of menu items.
	 */
	public function test_menu_items_filter_is_applied() {
		$filter_called = false;

		add_filter(
			'edac_admin_toolbar_menu_items',
			function ( $menu_items ) use ( &$filter_called ) {
				$filter_called = true;
				// Add a custom menu item.
				$menu_items[] = [
					'id'     => 'custom-test-item',
					'parent' => 'accessibility-checker',
					'title'  => 'Test Item',
					'href'   => 'http://example.com',
				];
				return $menu_items;
			} 
		);

		// Mock constants.
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', false );
		}

		$this->admin_toolbar->add_toolbar_items( $this->wp_admin_bar );

		$this->assertTrue( $filter_called, 'Filter should have been called' );
	}

	/**
	 * Test that Settings menu item has correct parameters.
	 */
	public function test_settings_menu_item_parameters() {
		$menu_items = $this->get_default_menu_items_via_reflection();

		$settings_item = array_filter(
			$menu_items,
			function ( $item ) {
				return 'accessibility-checker-settings' === $item['id'];
			} 
		);

		$this->assertCount( 1, $settings_item );
		$settings_item = array_values( $settings_item )[0];

		$this->assertEquals( 'accessibility-checker', $settings_item['parent'] );
		$this->assertEquals( 'Settings', $settings_item['title'] );
		$this->assertEquals( admin_url( 'admin.php?page=accessibility_checker_settings' ), $settings_item['href'] );
	}

	/**
	 * Test that Fixes menu item has correct parameters.
	 */
	public function test_fixes_menu_item_parameters() {
		$menu_items = $this->get_default_menu_items_via_reflection();

		$fixes_item = array_filter(
			$menu_items,
			function ( $item ) {
				return 'accessibility-checker-fixes' === $item['id'];
			} 
		);

		$this->assertCount( 1, $fixes_item );
		$fixes_item = array_values( $fixes_item )[0];

		$this->assertEquals( 'accessibility-checker', $fixes_item['parent'] );
		$this->assertEquals( 'Fixes', $fixes_item['title'] );
		$this->assertEquals( admin_url( 'admin.php?page=accessibility_checker_settings&tab=fixes' ), $fixes_item['href'] );
	}

	/**
	 * Test that Get Pro menu item has correct parameters including accessibility attributes.
	 */
	public function test_get_pro_menu_item_parameters() {
		// Mock constants to ensure pro item is shown.
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', false );
		}

		$menu_items = $this->get_default_menu_items_via_reflection();

		$pro_item = array_filter(
			$menu_items,
			function ( $item ) {
				return 'accessibility-checker-pro' === $item['id'];
			} 
		);

		$this->assertCount( 1, $pro_item );
		$pro_item = array_values( $pro_item )[0];

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
	 */
	public function test_get_pro_link_uses_generate_link_function() {
		// Mock the function.
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			/**
			 * Mock function for testing pro link generation.
			 *
			 * @param array $args Query arguments for the link.
			 * @return string Mocked pro link URL.
			 */
			function edac_generate_link_type( $args ) {
				return 'https://mocked-pro-link.com?' . http_build_query( $args );
			}
		}

		// Mock constants to ensure pro item is shown.
		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			define( 'EDAC_KEY_VALID', false );
		}

		$menu_items = $this->get_default_menu_items_via_reflection();

		$pro_item = array_filter(
			$menu_items,
			function ( $item ) {
				return 'accessibility-checker-pro' === $item['id'];
			} 
		);

		$this->assertCount( 1, $pro_item );
		$pro_item = array_values( $pro_item )[0];

		$this->assertStringContainsString( 'mocked-pro-link.com', $pro_item['href'] );
		$this->assertStringContainsString( 'utm-content=admin-toolbar', $pro_item['href'] );
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
}
