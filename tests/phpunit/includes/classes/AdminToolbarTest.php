<?php
/**
 * PHPUnit tests for the Admin_Toolbar class.
 *
 * @package Accessibility_Checker\Tests
 */

use EDAC\Inc\Admin_Toolbar;

/**
 * Class Admin_Toolbar_Test
 *
 * @covers \EDAC\Inc\Admin_Toolbar
 */
class Admin_Toolbar_Test extends WP_UnitTestCase {
	/**
	 * Test instantiation of Admin_Toolbar class.
	 */
	public function test_can_instantiate_class() {
		$toolbar = new Admin_Toolbar();
		$this->assertInstanceOf( Admin_Toolbar::class, $toolbar );
	}

	/**
	 * Test that init() adds the admin_bar_menu action.
	 */
	public function test_init_adds_action() {
		$toolbar = new Admin_Toolbar();
		$toolbar->init();
		$this->assertArrayHasKey( 'admin_bar_menu', $GLOBALS['wp_filter'] );
	}

	/**
	 * Test add_toolbar_items() does not add menu for non-admin user.
	 */
	public function test_add_toolbar_items_for_non_admin_user() {
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$toolbar  = new Admin_Toolbar();
		$mock_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();
		$mock_bar->expects( $this->never() )->method( 'add_menu' );
		$toolbar->add_toolbar_items( $mock_bar );
	}

	/**
	 * Test add_toolbar_items() adds menus for admin user.
	 */
	public function test_add_toolbar_items_adds_menus_for_admin_user() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$toolbar     = new Admin_Toolbar();
		$added_items = [];

		$mock_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();
		$mock_bar->expects( $this->atLeastOnce() )
			->method( 'add_menu' )
			->willReturnCallback(
				function ( $item ) use ( &$added_items ) {
					$added_items[] = $item;
				}
			);

		$toolbar->add_toolbar_items( $mock_bar );

		$ids = array_column( $added_items, 'id' );
		$this->assertContains( 'accessibility-checker', $ids );
		$this->assertContains( 'accessibility-checker-settings', $ids );
		$this->assertContains( 'accessibility-checker-fixes', $ids );
	}

	/**
	 * Test add_toolbar_items() includes Get Pro submenu when pro is not installed.
	 */
	public function test_add_toolbar_items_includes_get_pro_submenu() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$toolbar     = new Admin_Toolbar();
		$added_items = [];

		$mock_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();
		$mock_bar->expects( $this->atLeastOnce() )
			->method( 'add_menu' )
			->willReturnCallback(
				function ( $item ) use ( &$added_items ) {
					$added_items[] = $item;
				}
			);

		$toolbar->add_toolbar_items( $mock_bar );

		$ids = array_column( $added_items, 'id' );
		$this->assertContains( 'accessibility-checker-pro', $ids );
	}

	/**
	 * Test get_default_menu_items() returns a non-empty array with required keys.
	 */
	public function test_get_default_menu_items_returns_array() {
		$reflection = new \ReflectionClass( Admin_Toolbar::class );
		$method     = $reflection->getMethod( 'get_default_menu_items' );
		$method->setAccessible( true );
		$toolbar = new Admin_Toolbar();
		$items   = $method->invoke( $toolbar );
		$this->assertIsArray( $items );
		$this->assertNotEmpty( $items );
		$this->assertArrayHasKey( 'id', $items[0] );
	}
}
