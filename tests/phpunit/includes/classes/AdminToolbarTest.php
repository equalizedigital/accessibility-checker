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
		$user_id = $this->set_current_user_with_role( 'subscriber' );

		try {
			$toolbar  = new Admin_Toolbar();
			$mock_bar = $this->getMockBuilder( stdClass::class )
				->addMethods( [ 'add_menu' ] )
				->getMock();
			$mock_bar->expects( $this->never() )->method( 'add_menu' );
			$toolbar->add_toolbar_items( $mock_bar );
		} finally {
			// This class is a plain test case with no database rollback, so always clean up.
			wp_set_current_user( 0 );
			wp_delete_user( $user_id );
		}
	}

	/**
	 * Test add_toolbar_items() adds menu for admin user.
	 */
	public function test_add_toolbar_items_for_admin_user() {
		$user_id = $this->set_current_user_with_role( 'administrator' );

		try {
			$toolbar  = new Admin_Toolbar();
			$mock_bar = $this->getMockBuilder( stdClass::class )
				->addMethods( [ 'add_menu' ] )
				->getMock();
			$mock_bar->expects( $this->atLeastOnce() )->method( 'add_menu' );
			$toolbar->add_toolbar_items( $mock_bar );
		} finally {
			// This class is a plain test case with no database rollback, so always clean up.
			wp_set_current_user( 0 );
			wp_delete_user( $user_id );
		}
	}

	/**
	 * Creates a user with the given role and sets them as the current user.
	 *
	 * @param string $role Role to assign to the user.
	 * @return int The created user ID.
	 */
	private function set_current_user_with_role( $role ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$user_id = wp_insert_user(
			[
				'user_login' => 'edac_toolbar_test_' . $role . '_' . wp_generate_password( 8, false ),
				'user_pass'  => 'password',
				'role'       => $role,
			]
		);
		$this->assertIsInt( $user_id, 'The test user should be created.' );

		$user = get_user_by( 'id', $user_id );
		$this->assertInstanceOf( WP_User::class, $user, 'The test user should exist.' );
		$this->assertContains( $role, (array) $user->roles, 'The test user should have the requested role.' );

		wp_set_current_user( $user_id );

		return $user_id;
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

	/**
	 * Test get_default_menu_items() handles missing EDAC_KEY_VALID when pro is defined.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_default_menu_items_handles_missing_edac_key_valid_constant() {
		if ( defined( 'EDAC_KEY_VALID' ) ) {
			$this->markTestSkipped( 'EDAC_KEY_VALID is already defined in this test environment.' );
		}
		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', '1.0.0' );
		}

		$reflection = new \ReflectionClass( Admin_Toolbar::class );
		$method     = $reflection->getMethod( 'get_default_menu_items' );
		$method->setAccessible( true );
		$toolbar = new Admin_Toolbar();
		$items   = $method->invoke( $toolbar );

		$pro_item = null;
		foreach ( $items as $item ) {
			if ( 'accessibility-checker-pro' === $item['id'] ) {
				$pro_item = $item;
				break;
			}
		}

		$this->assertNotNull( $pro_item );
	}

	/**
	 * Test pro menu link uses the expected UTM content parameter key.
	 */
	public function test_get_default_menu_items_pro_link_uses_utm_content() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		$reflection = new \ReflectionClass( Admin_Toolbar::class );
		$method     = $reflection->getMethod( 'get_default_menu_items' );
		$method->setAccessible( true );
		$toolbar = new Admin_Toolbar();
		$items   = $method->invoke( $toolbar );

		$pro_item = null;
		foreach ( $items as $item ) {
			if ( 'accessibility-checker-pro' === $item['id'] ) {
				$pro_item = $item;
				break;
			}
		}

		$this->assertNotNull( $pro_item );
		$this->assertStringContainsString( 'utm_content=admin-toolbar', $pro_item['href'] );
		$this->assertStringNotContainsString( 'utm-content=admin-toolbar', $pro_item['href'] );
	}
}
