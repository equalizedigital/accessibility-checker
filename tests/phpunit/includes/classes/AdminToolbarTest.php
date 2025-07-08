<?php
/**
 * PHPUnit tests for the Admin_Toolbar class.
 *
 * @package Accessibility_Checker\Tests
 */

use PHPUnit\Framework\TestCase;
use EDAC\Inc\Admin_Toolbar;

/**
 * Class Admin_Toolbar_Test
 *
 * @covers \EDAC\Inc\Admin_Toolbar
 */
class Admin_Toolbar_Test extends TestCase {
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
		$toolbar = new Admin_Toolbar();
		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		if ( ! function_exists( 'current_user_can' ) ) {
			/**
			 * Mock current_user_can for testing.
			 *
			 * @param string $cap Capability.
			 * @return bool
			 */
			function current_user_can( $cap = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				return false;
			}
		}
		$mock_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();
		$mock_bar->expects( $this->never() )->method( 'add_menu' );
		$toolbar->add_toolbar_items( $mock_bar );
	}

	/**
	 * Test add_toolbar_items() adds menu for admin user.
	 */
	public function test_add_toolbar_items_for_admin_user() {
		$toolbar = new Admin_Toolbar();
		// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		if ( ! function_exists( 'current_user_can' ) ) {
			/**
			 * Mock current_user_can for testing.
			 *
			 * @param string $cap Capability.
			 * @return bool
			 */
			function current_user_can( $cap = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				return true;
			}
		}
		$mock_bar = $this->getMockBuilder( stdClass::class )
			->addMethods( [ 'add_menu' ] )
			->getMock();
		$mock_bar->expects( $this->atLeastOnce() )->method( 'add_menu' );
		$toolbar->add_toolbar_items( $mock_bar );
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
