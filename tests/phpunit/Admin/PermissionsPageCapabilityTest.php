<?php
/**
 * Permissions Page tests.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\PermissionsPage;

/**
 * Covers the dual-review fix (finding #4, 2026-08-13): the Permissions tab -
 * which grants OTHER roles capabilities, including the site-wide/global
 * dismiss oversteps - used to be gated by the filterable
 * edac_filter_settings_capability (default manage_options, but a site can
 * lower it, e.g. to let editors into Settings). A lowered filter would have
 * let those editors reach the Permissions tab and grant their own role the
 * oversteps. PermissionsPage now hard-codes manage_options for tab
 * visibility, content rendering, asset enqueue, and the save handler,
 * independent of whatever capability it's constructed with.
 */
class PermissionsPageCapabilityTest extends WP_UnitTestCase {

	/**
	 * Reset the current user after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * The tab item's capability must always be manage_options, even when the
	 * page is constructed with a lower, filtered settings capability.
	 *
	 * @return void
	 */
	public function test_tab_item_capability_is_always_manage_options() {
		$page = new PermissionsPage( 'edit_posts' );

		$tab_items = $page->add_permissions_tab( [] );

		$this->assertSame( 'manage_options', $tab_items[0]['capability'] );
	}

	/**
	 * A user who meets the injected (lower) settings capability but not
	 * manage_options must not see the tab content rendered.
	 *
	 * @return void
	 */
	public function test_tab_content_denied_for_non_admin_with_lower_settings_capability() {
		$page = new PermissionsPage( 'edit_posts' );

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		$this->assertTrue( current_user_can( 'edit_posts' ), 'Precondition: editor has the injected capability.' );
		$this->assertFalse( current_user_can( 'manage_options' ), 'Precondition: editor is not an admin.' );

		ob_start();
		$page->add_permissions_tab_content( PermissionsPage::PAGE_TAB_SLUG );
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'A non-admin who meets only the injected settings capability must not see the Permissions tab content.' );
	}

	/**
	 * An administrator must still see the tab content rendered.
	 *
	 * @return void
	 */
	public function test_tab_content_rendered_for_admin() {
		$page = new PermissionsPage( 'edit_posts' );

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		ob_start();
		$page->add_permissions_tab_content( PermissionsPage::PAGE_TAB_SLUG );
		$output = ob_get_clean();

		$this->assertNotSame( '', $output, 'An administrator must see the Permissions tab content rendered.' );
	}

	/**
	 * Render_page() (the PageInterface-required entry point) must be gated
	 * the same as add_permissions_tab_content() - it was found unguarded
	 * during a 2026-08-17 manual review pass, rendering the full capability
	 * role map and grant UI for anyone who could reach it directly.
	 *
	 * @return void
	 */
	public function test_render_page_denied_for_non_admin_with_lower_settings_capability() {
		$page = new PermissionsPage( 'edit_posts' );

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		ob_start();
		$page->render_page();
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'A non-admin who meets only the injected settings capability must not see the Permissions page rendered.' );
	}

	/**
	 * An administrator must still see the page rendered via render_page().
	 *
	 * @return void
	 */
	public function test_render_page_rendered_for_admin() {
		$page = new PermissionsPage( 'edit_posts' );

		$admin_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		ob_start();
		$page->render_page();
		$output = ob_get_clean();

		$this->assertNotSame( '', $output, 'An administrator must see the Permissions page rendered via render_page().' );
	}

	/**
	 * The save handler must reject a non-admin even when they meet the
	 * injected (lower) settings capability - the fix this test guards
	 * against would have let such a user grant their own role a capability
	 * (including the global-dismiss overstep) through this action.
	 *
	 * @return void
	 */
	public function test_handle_save_rejects_non_admin_with_lower_settings_capability() {
		$page = new PermissionsPage( 'edit_posts' );

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'You do not have permission to manage Accessibility Checker permissions.' );

		$page->handle_save();
	}
}
