<?php
/**
 * Tests for the dashboard widget registration and its empty-configuration notice.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Widgets;

/**
 * Tests for Widgets::dashboard_setup() and the no-posts notice rendered by
 * Widgets::render_dashboard_scan_summary().
 *
 * The widget output itself (UTM keys on the upgrade links) is covered in
 * WidgetsTest; this file covers the registration gate and the branch that
 * shows when no post type is selected to be checked.
 */
class WidgetsDashboardSetupTest extends WP_UnitTestCase {

	/**
	 * User ids created for the tests, keyed by role.
	 *
	 * @var array<string, int>
	 */
	private $user_ids = [];

	/**
	 * Loads the dashboard API and opens the dashboard screen.
	 *
	 * The wp_add_dashboard_widget() function lives in
	 * wp-admin/includes/dashboard.php, which the test environment does not
	 * load, and it registers the widget on whichever screen
	 * get_current_screen() returns. The screen is set directly rather than
	 * with set_current_screen() so the current_screen action does not fire.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		$GLOBALS['current_screen'] = WP_Screen::get( 'dashboard' );

		$this->user_ids['administrator'] = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->user_ids['subscriber']    = self::factory()->user->create( [ 'role' => 'subscriber' ] );
	}

	/**
	 * Removes the options and globals the tests set.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'edac_post_types' );
		wp_set_current_user( 0 );

		unset( $GLOBALS['wp_meta_boxes'], $GLOBALS['current_screen'] );

		parent::tearDown();
	}

	/**
	 * Tests the widget is registered only for a user who may see it.
	 *
	 * The gate is the edit_posts capability (via Helpers), so an administrator
	 * gets the widget and a subscriber gets nothing.
	 *
	 * @param string $role     Role the current user is given.
	 * @param bool   $expected Whether the widget should be registered.
	 *
	 * @dataProvider provider_dashboard_capability
	 * @covers \EDAC\Admin\Widgets::dashboard_setup
	 *
	 * @return void
	 */
	public function test_dashboard_setup_registers_the_widget_only_when_permitted( string $role, bool $expected ): void {
		wp_set_current_user( $this->user_ids[ $role ] );

		$GLOBALS['wp_meta_boxes'] = [];

		( new Widgets() )->dashboard_setup();

		$registered = isset( $GLOBALS['wp_meta_boxes']['dashboard']['normal']['core']['edac_dashboard_scan_summary'] );
		$this->assertSame( $expected, $registered );
	}

	/**
	 * Provides the roles and whether each may see the dashboard widget.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public function provider_dashboard_capability(): array {
		return [
			'subscriber may not' => [ 'subscriber', false ],
			'administrator may'  => [ 'administrator', true ],
		];
	}

	/**
	 * Tests the no-posts notice renders only when nothing is scannable.
	 *
	 * @param array $post_types Post types stored in the edac_post_types option.
	 * @param bool  $expected   Whether the notice should render.
	 *
	 * @dataProvider provider_no_posts_notice
	 * @covers \EDAC\Admin\Widgets::render_dashboard_scan_summary
	 *
	 * @return void
	 */
	public function test_no_posts_notice_renders_only_without_a_scannable_config( array $post_types, bool $expected ): void {
		wp_set_current_user( $this->user_ids['administrator'] );
		update_option( 'edac_post_types', $post_types );

		ob_start();
		( new Widgets() )->render_dashboard_scan_summary();
		$output = (string) ob_get_clean();

		$has_notice = ( false !== strpos( $output, 'edac-summary-notice-no-posts' ) );
		$this->assertSame( $expected, $has_notice );
	}

	/**
	 * Provides scannable post type configurations and whether the notice shows.
	 *
	 * @return array<string, array{0: array, 1: bool}>
	 */
	public function provider_no_posts_notice(): array {
		return [
			'nothing selected' => [ [], true ],
			'post and page'    => [ [ 'post', 'page' ], false ],
		];
	}
}
