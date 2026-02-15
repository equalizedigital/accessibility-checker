<?php
/**
 * Tests for widget output.
 *
 * @package accessibility-checker
 */

use EDAC\Admin\Widgets;

/**
 * Tests for dashboard widget links.
 */
class WidgetsTest extends WP_UnitTestCase {

	/**
	 * Set up test state.
	 */
	protected function setUp(): void {
		parent::setUp();

		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		update_option( 'edac_post_types', [ 'post', 'page' ] );

		register_post_type(
			'edac_book',
			[
				'public' => true,
				'label'  => 'Books',
			]
		);
	}

	/**
	 * Clean up test state.
	 */
	protected function tearDown(): void {
		delete_option( 'edac_post_types' );
		if ( post_type_exists( 'edac_book' ) ) {
			unregister_post_type( 'edac_book' );
		}
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Ensure dashboard widget uses underscore UTM keys for upgrade links.
	 */
	public function testRenderDashboardScanSummaryUsesUnderscoreUtmKeys() {
		if (
			! function_exists( 'edac_generate_link_type' ) ||
			! function_exists( 'edac_link_wrapper' ) ||
			! function_exists( 'edac_get_post_type_label' )
		) {
			$this->markTestSkipped( 'Required helper functions are not available in this test environment.' );
		}

		$widgets = new Widgets();

		ob_start();
		$widgets->render_dashboard_scan_summary();
		$output = ob_get_clean();

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'utm_campaign=dashboard-widget', $output );
		$this->assertStringContainsString( 'utm_content=upgrade-to-edacp', $output );
		$this->assertStringNotContainsString( 'utm-campaign=dashboard-widget', $output );
		$this->assertStringNotContainsString( 'utm-content=upgrade-to-edacp', $output );
	}
}
