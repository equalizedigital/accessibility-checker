<?php
/**
 * Tests UTM key usage in dashboard widget upgrade links.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Widgets;

/**
 * Tests for widget upgrade-link UTM keys.
 */
class edac_WidgetsUtmTest extends WP_UnitTestCase {

	/**
	 * Set up options needed for widget rendering.
	 */
	protected function setUp(): void {
		parent::setUp();
		update_option( 'edac_post_types', [ 'post', 'page' ] );
	}

	/**
	 * Clean up option state after each test.
	 */
	protected function tearDown(): void {
		delete_option( 'edac_post_types' );
		parent::tearDown();
	}

	/**
	 * Upgrade links in dashboard widget should use underscore UTM keys.
	 */
	public function test_dashboard_upgrade_links_use_underscore_utm_keys() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		delete_user_meta( $user_id, 'edac_dashboard_cta_dismissed' );

		$widget = new Widgets();
		ob_start();
		$widget->render_dashboard_scan_summary();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'utm_content=upgrade-to-edacp', $output );
		$this->assertStringNotContainsString( 'utm-content=upgrade-to-edacp', $output );
	}
}
