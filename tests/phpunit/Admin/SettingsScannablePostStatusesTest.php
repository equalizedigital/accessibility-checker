<?php
/**
 * Class SettingsScannablePostStatusesTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Settings;

/**
 * Test cases for Settings::get_scannable_post_statuses().
 */
class SettingsScannablePostStatusesTest extends WP_UnitTestCase {
	/**
	 * Ensure filter returning a string is coerced to an array.
	 */
	public function test_get_scannable_post_statuses_coerces_filter_to_array() {
		$filter = function () {
			return 'publish';
		};

		add_filter( 'edac_scannable_post_statuses', $filter );

		$this->assertSame( [ 'publish' ], Settings::get_scannable_post_statuses() );

		remove_filter( 'edac_scannable_post_statuses', $filter );
	}
}
