<?php
/**
 * Tests the helper function that adds a scheme to urls that don't have one.
 *
 * @package accessibility-checker
 */

/**
 * Test class for validating the helper function that adds a scheme to urls works
 * as it was intended when written.
 */
class UrlAddSchemeIfNotExistingTest extends WP_UnitTestCase {

	/**
	 * Test that the function adds a scheme to a url that doesn't have one and returns relative urls unchanged.
	 */
	public function test_url_add_scheme_if_not_existing() {
		$url      = '//example.com';
		$expected = 'https://example.com';
		$this->assertEquals( $expected, edac_url_add_scheme_if_not_existing( $url, 'https' ) );

		$url_http = '//example.com';
		$expected = 'http://example.com';
		$this->assertEquals( $expected, edac_url_add_scheme_if_not_existing( $url_http, 'http' ) );

		$url_one_slash = '/example.com';
		$this->assertEquals( $url_one_slash, edac_url_add_scheme_if_not_existing( $url_one_slash ) );

		$url_no_slash = 'example.com';
		$this->assertStringStartsWith( 'http', edac_url_add_scheme_if_not_existing( $url_no_slash ) );
	}

	/**
	 * Test that the function doesn't add a scheme to a url that already has one.
	 */
	public function test_url_add_scheme_if_not_existing_with_scheme() {
		$url = 'https://example.com';
		$this->assertEquals( $url, edac_url_add_scheme_if_not_existing( $url ) );

		$url_http = 'http://example.com/';
		$this->assertEquals( $url_http, edac_url_add_scheme_if_not_existing( $url_http, 'http' ) );
	}

	/**
	 * Test that the function doesn't add a scheme to a url that already has one, even when not http*.
	 */
	public function test_url_add_scheme_if_not_existing_unmoidfied_with_ftp() {
		$ftp_url = 'ftp://example.com';
		$this->assertEquals( $ftp_url, edac_url_add_scheme_if_not_existing( $ftp_url ) );
	}

	/**
	 * Test that the function doesn't add a scheme to a local or relative url.
	 */
	public function test_url_add_scheme_if_not_existing_unmoidfied_when_local_or_relative() {
		$local_path = '/wp-content/uploads/2024/03/image.gif';
		$this->assertEquals( $local_path, edac_url_add_scheme_if_not_existing( $local_path ) );

		$relative_url = '/about.gif';
		$this->assertEquals( $relative_url, edac_url_add_scheme_if_not_existing( $relative_url ) );
	}
}
