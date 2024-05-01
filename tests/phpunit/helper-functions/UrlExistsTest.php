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
		$url = 'https://httpbin.org/status/200';
		$this->assertTrue( edac_url_exists( $url, 20 ) );
	}

	/**
	 * Test that we get false on a non-2xx status code.
	 *
	 * @group external-http
	 */
	public function test_url_does_not_exist() {
		$url = 'https://httpbin.org/status/404';
		$this->assertFalse( edac_url_exists( $url ) );
		$url = 'https://httpbin.org/status/418';
		$this->assertFalse( edac_url_exists( $url ) );
	}
}
