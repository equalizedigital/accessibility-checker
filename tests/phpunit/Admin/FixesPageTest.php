<?php
/**
 * Fixes Page tests.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

/**
 * Fixes Page test case.
 */
class FixesPageTest extends WP_UnitTestCase {

	/**
	 * Test register_settings_sections handles scalar filter output without warnings.
	 */
	public function test_register_settings_sections_handles_scalar_filter_output() {
		$page = new FixesPage( 'manage_options' );

		add_filter(
			'edac_filter_fixes_settings_sections',
			static function () {
				return 'not-an-array';
			}
		);

		$handler = static function () {
			throw new \RuntimeException( 'PHP warning captured during register_settings_sections.' );
		};
		set_error_handler( $handler ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in test to convert warnings into failures.

		try {
			$page->register_settings_sections();
			$this->assertTrue( true );
		} catch ( \RuntimeException $exception ) {
			$this->fail( 'register_settings_sections should ignore scalar filter output without warnings.' );
		} finally {
			restore_error_handler();
			remove_all_filters( 'edac_filter_fixes_settings_sections' );
		}
	}
}
