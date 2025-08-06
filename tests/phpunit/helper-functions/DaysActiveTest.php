<?php
/**
 * Class DaysActiveTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_days_active() function.
 */
class DaysActiveTest extends WP_UnitTestCase {

	/**
	 * Test days active calculation with valid activation date.
	 */
	public function test_days_active_with_valid_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set a mock activation date 10 days ago.
		$ten_days_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-10 days' ) );
		update_option( 'edac_activation_date', $ten_days_ago );

		$result = edac_days_active();

		// Should return a positive number around 10.
		$this->assertIsNumeric( $result );
		$this->assertGreaterThanOrEqual( 9, $result );
		$this->assertLessThanOrEqual( 11, $result ); // Allow for timing differences.

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with recent activation date.
	 */
	public function test_days_active_with_recent_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set activation date to 1 hour ago.
		$one_hour_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );
		update_option( 'edac_activation_date', $one_hour_ago );

		$result = edac_days_active();

		// Should return 0 for same day activation.
		$this->assertIsNumeric( $result );
		$this->assertSame( 0.0, $result );

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with future date (edge case).
	 */
	public function test_days_active_with_future_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set activation date to 5 days in the future.
		$five_days_future = gmdate( 'Y-m-d H:i:s', strtotime( '+5 days' ) );
		update_option( 'edac_activation_date', $five_days_future );

		$result = edac_days_active();

		// Should return absolute value, so 5 days.
		$this->assertIsNumeric( $result );
		$this->assertGreaterThanOrEqual( 4, $result );
		$this->assertLessThanOrEqual( 6, $result ); // Allow for timing differences.

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with no activation date set.
	 */
	public function test_days_active_with_no_activation_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Ensure the option doesn't exist.
		delete_option( 'edac_activation_date' );

		$result = edac_days_active();

		// Should return 0 when no activation date is set.
		$this->assertIsNumeric( $result );
		$this->assertSame( 0, $result );
	}

	/**
	 * Test days active calculation with empty activation date.
	 */
	public function test_days_active_with_empty_activation_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set empty activation date.
		update_option( 'edac_activation_date', '' );

		$result = edac_days_active();

		// Should return 0 for empty activation date.
		$this->assertIsNumeric( $result );
		$this->assertSame( 0, $result );

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with malformed date.
	 */
	public function test_days_active_with_malformed_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set malformed activation date.
		update_option( 'edac_activation_date', 'not-a-date' );

		$result = edac_days_active();

		// Should return a number (behavior may vary based on strtotime).
		$this->assertIsNumeric( $result );

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with very old activation date.
	 */
	public function test_days_active_with_old_date() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// Set activation date to 365 days ago.
		$one_year_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-365 days' ) );
		update_option( 'edac_activation_date', $one_year_ago );

		$result = edac_days_active();

		// Should return a large positive number around 365.
		$this->assertIsNumeric( $result );
		$this->assertGreaterThanOrEqual( 364, $result );
		$this->assertLessThanOrEqual( 366, $result ); // Allow for leap years and timing.

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Test days active calculation with different date formats.
	 *
	 * @dataProvider date_format_data
	 *
	 * @param string $date_string The date string to test.
	 * @param string $description Test description.
	 */
	public function test_days_active_with_different_date_formats( $date_string, $description ) {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		update_option( 'edac_activation_date', $date_string );

		$result = edac_days_active();

		// Should always return a number.
		$this->assertIsNumeric( $result, "Failed for: $description" );
		$this->assertGreaterThanOrEqual( 0, $result, "Negative result for: $description" );

		// Clean up.
		delete_option( 'edac_activation_date' );
	}

	/**
	 * Data provider for different date formats.
	 */
	public function date_format_data() {
		$one_day_ago = strtotime( '-1 day' );

		return [
			'MySQL datetime format' => [
				'date_string' => gmdate( 'Y-m-d H:i:s', $one_day_ago ),
				'description' => 'MySQL datetime format',
			],
			'ISO 8601 format'       => [
				'date_string' => gmdate( 'c', $one_day_ago ),
				'description' => 'ISO 8601 format',
			],
			'Unix timestamp string' => [
				'date_string' => (string) $one_day_ago,
				'description' => 'Unix timestamp string',
			],
			'Date only format'      => [
				'date_string' => gmdate( 'Y-m-d', $one_day_ago ),
				'description' => 'Date only format',
			],
		];
	}

	/**
	 * Test that the function handles timezone differences appropriately.
	 */
	public function test_days_active_timezone_handling() {
		// Skip this test if WordPress functions aren't available.
		if ( ! function_exists( 'update_option' ) || ! function_exists( 'delete_option' ) ) {
			$this->markTestSkipped( 'WordPress option functions not available in test environment.' );
		}

		// The function uses gmdate() which is UTC-based.
		// Set a specific time that's close to midnight in UTC.
		$utc_yesterday_23_59 = gmdate( 'Y-m-d H:i:s', strtotime( 'yesterday 23:59:00 UTC' ) );
		update_option( 'edac_activation_date', $utc_yesterday_23_59 );

		$result = edac_days_active();

		// Should return 1 day since it's the previous UTC day.
		$this->assertIsNumeric( $result );
		$this->assertGreaterThanOrEqual( 0, $result );
		$this->assertLessThanOrEqual( 2, $result ); // Allow for timing edge cases.

		// Clean up.
		delete_option( 'edac_activation_date' );
	}
}
