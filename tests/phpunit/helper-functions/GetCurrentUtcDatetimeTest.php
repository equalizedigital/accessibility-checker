<?php
/**
 * Class GetCurrentUtcDatetimeTest
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_get_current_utc_datetime().
 *
 * @since 1.35.0
 */
class GetCurrentUtcDatetimeTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_get_current_utc_datetime function.
	 */
	public function test_edac_get_current_utc_datetime() {
		$datetime = edac_get_current_utc_datetime();

		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
			$datetime
		);

		$timestamp = strtotime( $datetime . ' UTC' );
		$this->assertNotFalse( $timestamp );
		$this->assertLessThanOrEqual( 5, abs( time() - $timestamp ) );
	}
}
