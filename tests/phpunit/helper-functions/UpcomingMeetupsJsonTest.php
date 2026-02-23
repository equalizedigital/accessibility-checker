<?php
/**
 * Class UpcomingMeetupsJsonTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_get_upcoming_meetups_json() function.
 */
class UpcomingMeetupsJsonTest extends WP_UnitTestCase {

	/**
	 * Ensure invalid JSON responses do not trigger notices.
	 */
	public function test_edac_get_upcoming_meetups_json_handles_invalid_json() {
		$meetup = 'test-meetup';
		$count  = 1;
		$key    = '_upcoming_meetups__' . $meetup . '__' . $count;

		delete_transient( $key );
		delete_option( $key . '__stale' );

		$filter = function () {
			return [
				'headers'  => [],
				'body'     => 'not json',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};

		add_filter( 'pre_http_request', $filter, 10, 3 );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Needed to convert warnings into test failures.
		set_error_handler(
			function ( $severity, $message, $file, $line ) {
				throw new \ErrorException( $message, 0, $severity, $file, $line ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped,  -- This is just rethrowing the error as an exception for testing purposes.
			}
		);

		try {
			$result = edac_get_upcoming_meetups_json( $meetup, $count );
		} finally {
			restore_error_handler();
			remove_filter( 'pre_http_request', $filter, 10 );
		}

		$this->assertSame( [], $result );
	}
}
