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
	 * Build a mock admin bar that records the nodes passed to add_menu().
	 *
	 * @return object Mock with a public $nodes array.
	 */
	private function get_recording_bar() {
		return new class() {
			/**
			 * Recorded nodes.
			 *
			 * @var array
			 */
			public $nodes = [];

			/**
			 * Record a node.
			 *
			 * @param array $node Node args.
			 * @return void
			 */
			public function add_menu( $node ) {
				$this->nodes[ $node['id'] ] = $node;
			}
		};
	}

	/**
	 * Run a callback as an administrator and always clean up the user.
	 *
	 * @param callable $callback Receives the user ID.
	 * @return void
	 */
	private function as_admin( callable $callback ) {
		$user_id = $this->set_current_user_with_role( 'administrator' );

		try {
			$callback( $user_id );
		} finally {
			remove_all_filters( 'edac_show_admin_toolbar_item' );
			remove_all_filters( 'edac_admin_toolbar_display_mode' );
			wp_set_current_user( 0 );
			wp_delete_user( $user_id );
		}
	}

	/**
	 * Default mode shows the icon and visible text.
	 */
	public function test_default_mode_shows_icon_and_text() {
		$this->as_admin(
			function () {
				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$this->assertArrayHasKey( 'accessibility-checker', $bar->nodes );
				$this->assertStringContainsString( 'Accessibility Checker', $bar->nodes['accessibility-checker']['title'] );
				$this->assertStringNotContainsString( 'screen-reader-text', $bar->nodes['accessibility-checker']['title'] );
			}
		);
	}

	/**
	 * Icon-only mode keeps an accessible name and a tooltip.
	 */
	public function test_icon_mode_keeps_accessible_name_and_tooltip() {
		$this->as_admin(
			function ( $user_id ) {
				update_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, Admin_Toolbar::MODE_ICON );

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$node = $bar->nodes['accessibility-checker'];
				$this->assertStringContainsString( 'ab-icon', $node['title'] );
				$this->assertStringContainsString( '<span class="screen-reader-text">Accessibility Checker</span>', $node['title'] );
				$this->assertSame( 'Accessibility Checker', $node['meta']['title'] );
				$this->assertArrayHasKey( 'accessibility-checker-settings', $bar->nodes, 'Child items are still added.' );
			}
		);
	}

	/**
	 * Hidden mode adds nothing.
	 */
	public function test_hidden_mode_adds_no_nodes() {
		$this->as_admin(
			function ( $user_id ) {
				update_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, Admin_Toolbar::MODE_HIDDEN );

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$this->assertSame( [], $bar->nodes );
			}
		);
	}

	/**
	 * An invalid stored value falls back to the default mode.
	 */
	public function test_invalid_stored_mode_falls_back_to_full() {
		$this->as_admin(
			function ( $user_id ) {
				update_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, 'bogus' );

				$this->assertSame( Admin_Toolbar::MODE_FULL, ( new Admin_Toolbar() )->get_display_mode( $user_id ) );
			}
		);
	}

	/**
	 * The code-level filter removes the item entirely, overriding the user preference.
	 */
	public function test_show_item_filter_false_removes_item() {
		$this->as_admin(
			function () {
				add_filter( 'edac_show_admin_toolbar_item', '__return_false' );

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$this->assertSame( [], $bar->nodes );
			}
		);
	}

	/**
	 * The filter receives the user ID being built for.
	 */
	public function test_show_item_filter_receives_user_id() {
		$this->as_admin(
			function ( $user_id ) {
				$received = null;
				add_filter(
					'edac_show_admin_toolbar_item',
					function ( $enabled, $id ) use ( &$received ) {
						$received = $id;
						return $enabled;
					},
					10,
					2
				);

				( new Admin_Toolbar() )->add_toolbar_items( $this->get_recording_bar() );

				$this->assertSame( $user_id, $received );
			}
		);
	}

	/**
	 * The display mode filter can override the stored preference.
	 */
	public function test_display_mode_filter_overrides_preference() {
		$this->as_admin(
			function ( $user_id ) {
				add_filter(
					'edac_admin_toolbar_display_mode',
					function () {
						return Admin_Toolbar::MODE_HIDDEN;
					}
				);

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$this->assertSame( [], $bar->nodes );
				$this->assertSame( Admin_Toolbar::MODE_HIDDEN, ( new Admin_Toolbar() )->get_display_mode( $user_id ) );
			}
		);
	}

	/**
	 * Saving stores icon/hidden, ignores invalid values, and removes the meta for the default.
	 */
	public function test_save_profile_fields_stores_and_clears_preference() {
		$this->as_admin(
			function ( $user_id ) {
				$toolbar = new Admin_Toolbar();

				$_POST[ Admin_Toolbar::USER_META_KEY ] = Admin_Toolbar::MODE_ICON;
				$toolbar->save_profile_fields( $user_id );
				$this->assertSame( Admin_Toolbar::MODE_ICON, get_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, true ) );

				$_POST[ Admin_Toolbar::USER_META_KEY ] = 'nonsense';
				$toolbar->save_profile_fields( $user_id );
				$this->assertSame( Admin_Toolbar::MODE_ICON, get_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, true ), 'Invalid values are ignored.' );

				$_POST[ Admin_Toolbar::USER_META_KEY ] = Admin_Toolbar::MODE_FULL;
				$toolbar->save_profile_fields( $user_id );
				$this->assertSame( '', get_user_meta( $user_id, Admin_Toolbar::USER_META_KEY, true ) );

				unset( $_POST[ Admin_Toolbar::USER_META_KEY ] );
			}
		);
	}

	/**
	 * A user cannot save another user's preference without the edit_user capability.
	 */
	public function test_save_profile_fields_requires_capability() {
		$editor_id = $this->set_current_user_with_role( 'editor' );
		$admin_id  = wp_insert_user(
			[
				'user_login' => 'edac_toolbar_target_' . wp_generate_password( 8, false ),
				'user_pass'  => 'password',
				'role'       => 'administrator',
			]
		);

		try {
			$_POST[ Admin_Toolbar::USER_META_KEY ] = Admin_Toolbar::MODE_HIDDEN;
			( new Admin_Toolbar() )->save_profile_fields( $admin_id );
			$this->assertSame( '', get_user_meta( $admin_id, Admin_Toolbar::USER_META_KEY, true ) );
		} finally {
			unset( $_POST[ Admin_Toolbar::USER_META_KEY ] );
			wp_set_current_user( 0 );
			wp_delete_user( $editor_id );
			wp_delete_user( $admin_id );
		}
	}

	/**
	 * Profile fields render for admins, and not when the filter removes the item.
	 */
	public function test_profile_fields_render_and_respect_filter() {
		$this->as_admin(
			function ( $user_id ) {
				$toolbar = new Admin_Toolbar();
				$user    = get_user_by( 'id', $user_id );

				ob_start();
				$toolbar->render_profile_fields( $user );
				$html = ob_get_clean();
				$this->assertStringContainsString( 'type="radio"', $html );
				$this->assertStringContainsString( '<legend class="screen-reader-text">', $html );

				add_filter( 'edac_show_admin_toolbar_item', '__return_false' );
				ob_start();
				$toolbar->render_profile_fields( $user );
				$this->assertSame( '', ob_get_clean() );
			}
		);
	}

	/**
	 * Profile fields do not render for users who cannot manage options.
	 */
	public function test_profile_fields_hidden_for_non_admins() {
		$user_id = $this->set_current_user_with_role( 'editor' );

		try {
			ob_start();
			( new Admin_Toolbar() )->render_profile_fields( get_user_by( 'id', $user_id ) );
			$this->assertSame( '', ob_get_clean() );
		} finally {
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
