<?php
/**
 * Tests the helper function that checks if a URL exists.
 *
 * @package accessibility-checker
 */

/**
 * Test class for validating the helper function that checks if a URL exists.
 */
class UrlExistsTest extends WP_UnitTestCase {

	/**
	 * Test if URL exists.
	 *
	 * @group external-http
	 */
	public function test_url_exists() {
		$url        = 'https://postman-echo.com/status/200';
		$url_exists = edac_url_exists( $url, 10 );
		// this test url is flaky, so we try again one extra time if it fails.
		if ( ! $url_exists ) {
			$url_exists = edac_url_exists( $url, 20 );
		}
		$this->assertTrue( $url_exists );
	}

	/**
	 * Test that we get false on a non-2xx status code.
	 *
	 * @group external-http
	 */
	public function test_url_does_not_exist() {
		$url = 'https://postman-echo.com/status/404';
		$this->assertFalse( edac_url_exists( $url ) );
		$url = 'https://postman-echo.com/status/418';
		$this->assertFalse( edac_url_exists( $url ) );
	}
}
