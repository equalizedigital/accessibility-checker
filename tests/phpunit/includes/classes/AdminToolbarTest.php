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
	 * Whether the flyout keyboard script is enqueued after running the enqueue callback.
	 *
	 * @return bool
	 */
	private function flyout_script_is_enqueued(): bool {
		wp_dequeue_script( 'edac-admin-toolbar-flyout' );
		wp_deregister_script( 'edac-admin-toolbar-flyout' );
		add_filter( 'show_admin_bar', '__return_true' );

		( new Admin_Toolbar() )->enqueue_flyout_script();

		remove_filter( 'show_admin_bar', '__return_true' );
		$enqueued = wp_script_is( 'edac-admin-toolbar-flyout', 'enqueued' );

		wp_dequeue_script( 'edac-admin-toolbar-flyout' );
		wp_deregister_script( 'edac-admin-toolbar-flyout' );

		return $enqueued;
	}

	/**
	 * The keyboard script loads when the toolbar item is shown.
	 */
	public function test_flyout_script_is_enqueued_when_toolbar_is_shown() {
		$this->as_admin(
			function () {
				$this->assertTrue( $this->flyout_script_is_enqueued() );
			}
		);
	}

	/**
	 * The keyboard script is not loaded when the toolbar item is hidden for the user.
	 */
	public function test_flyout_script_is_not_enqueued_when_toolbar_is_hidden() {
		$this->as_admin(
			function () {
				add_filter(
					'edac_admin_toolbar_display_mode',
					static function () {
						return Admin_Toolbar::MODE_HIDDEN;
					}
				);

				$this->assertFalse( $this->flyout_script_is_enqueued() );
			}
		);
	}

	/**
	 * The keyboard script is not loaded when a filter removes the toolbar item.
	 */
	public function test_flyout_script_is_not_enqueued_when_item_is_disabled() {
		$this->as_admin(
			function () {
				add_filter( 'edac_show_admin_toolbar_item', '__return_false' );

				$this->assertFalse( $this->flyout_script_is_enqueued() );
			}
		);
	}

	/**
	 * The keyboard script is not loaded for users who cannot see the toolbar item.
	 */
	public function test_flyout_script_is_not_enqueued_without_manage_options() {
		$user_id = $this->set_current_user_with_role( 'editor' );

		try {
			$this->assertFalse( $this->flyout_script_is_enqueued() );
		} finally {
			wp_set_current_user( 0 );
			wp_delete_user( $user_id );
		}
	}

	/**
	 * The keyboard script is hooked to both the frontend and admin enqueue actions.
	 */
	public function test_flyout_script_is_hooked_for_frontend_and_admin() {
		$toolbar = new Admin_Toolbar();
		$toolbar->init();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', [ $toolbar, 'enqueue_flyout_script' ] ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', [ $toolbar, 'enqueue_flyout_script' ] ) );
	}

	/**
	 * The settings tabs appear as a flyout under Settings, in tab order.
	 */
	public function test_settings_tabs_are_nested_under_settings() {
		$this->as_admin(
			function () {
				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );

				$this->assertSame( 'accessibility-checker', $bar->nodes['accessibility-checker-settings']['parent'], 'Settings stays a top-level item.' );

				$children = array_keys(
					array_filter(
						$bar->nodes,
						static function ( $node ) {
							return 'accessibility-checker-settings' === ( $node['parent'] ?? '' );
						}
					)
				);

				$this->assertSame(
					[
						'accessibility-checker-settings-general',
						'accessibility-checker-fixes',
						'accessibility-checker-permissions',
						'accessibility-checker-accessibility-reports',
					],
					$children
				);

				$this->assertStringContainsString( 'tab=fixes', $bar->nodes['accessibility-checker-fixes']['href'] );
				$this->assertStringContainsString( 'tab=permissions', $bar->nodes['accessibility-checker-permissions']['href'] );
				$this->assertStringContainsString( 'tab=accessibility-reports', $bar->nodes['accessibility-checker-accessibility-reports']['href'] );
				$this->assertStringNotContainsString( 'tab=', $bar->nodes['accessibility-checker-settings-general']['href'] );
			}
		);
	}

	/**
	 * An add-on can add its own tab to the flyout and have it sort with the other settings items.
	 */
	public function test_add_on_can_add_a_settings_flyout_item() {
		$this->as_admin(
			function () {
				add_filter(
					'edac_admin_toolbar_menu_items',
					function ( $items ) {
						$items[] = $this->make_item(
							'addon-settings-tab',
							[
								'parent'    => 'accessibility-checker-settings',
								'menu_slug' => 'accessibility_checker_settings',
							]
						);
						return $items;
					}
				);

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );
				remove_all_filters( 'edac_admin_toolbar_menu_items' );

				$ids = array_keys( $bar->nodes );
				$this->assertGreaterThan(
					array_search( 'accessibility-checker-settings', $ids, true ),
					array_search( 'addon-settings-tab', $ids, true ),
					'The add-on tab is added after its parent.'
				);
				$this->assertSame( 'accessibility-checker-settings', $bar->nodes['addon-settings-tab']['parent'] );
			}
		);
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

	/**
	 * Build a toolbar item for the order tests.
	 *
	 * @param string $id    Node ID.
	 * @param array  $extra Extra node args.
	 * @return array
	 */
	private function make_item( string $id, array $extra = [] ): array {
		return array_merge(
			[
				'id'     => $id,
				'parent' => 'accessibility-checker',
				'title'  => $id,
				'href'   => 'https://example.com/' . $id,
			],
			$extra
		);
	}

	/**
	 * Toolbar items follow the same order as the admin submenu, whatever order they were added in.
	 */
	public function test_items_follow_admin_menu_order() {
		$this->as_admin(
			function () {
				add_filter(
					'edac_admin_toolbar_menu_items',
					function ( $items ) {
						$items[] = $this->make_item( 'accessibility-checker-archive-pages' );
						$items[] = $this->make_item( 'accessibility-checker-audit-history' );
						$items[] = $this->make_item( 'accessibility-checker-export-data' );
						$items[] = $this->make_item( 'accessibility-checker-full-site-scan' );
						$items[] = $this->make_item( 'accessibility-checker-issues-explorer' );
						return $items;
					}
				);

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );
				remove_all_filters( 'edac_admin_toolbar_menu_items' );

				$ids = array_keys( $bar->nodes );
				$this->assertSame( 'accessibility-checker', $ids[0], 'Parent node comes first.' );
				$this->assertSame(
					[
						'accessibility-checker-issues-explorer',
						'accessibility-checker-full-site-scan',
						'accessibility-checker-audit-history',
						'accessibility-checker-export-data',
						'accessibility-checker-archive-pages',
						'accessibility-checker-settings',
						'accessibility-checker-settings-general',
						'accessibility-checker-fixes',
						'accessibility-checker-permissions',
						'accessibility-checker-accessibility-reports',
					],
					array_slice( $ids, 1, 10 )
				);
			}
		);
	}

	/**
	 * Unmapped items land in the wildcard slot, and a menu_slug key places an item and is not passed to WordPress.
	 */
	public function test_unmapped_items_use_wildcard_and_menu_slug_is_honoured() {
		$this->as_admin(
			function () {
				add_filter(
					'edac_admin_toolbar_menu_items',
					function ( $items ) {
						$items[] = $this->make_item( 'third-party-unmapped' );
						$items[] = $this->make_item( 'third-party-mapped', [ 'menu_slug' => 'accessibility_checker_issues_explorer' ] );
						return $items;
					}
				);

				$bar = $this->get_recording_bar();
				( new Admin_Toolbar() )->add_toolbar_items( $bar );
				remove_all_filters( 'edac_admin_toolbar_menu_items' );

				$ids = array_keys( $bar->nodes );
				$this->assertSame( 'third-party-mapped', $ids[1], 'menu_slug places the item first among the pages.' );
				$this->assertLessThan(
					array_search( 'accessibility-checker-settings', $ids, true ),
					array_search( 'third-party-unmapped', $ids, true ),
					'Unmapped items come before Settings.'
				);
				$this->assertArrayNotHasKey( 'menu_slug', $bar->nodes['third-party-mapped'] );
			}
		);
	}
}
