<?php
/**
 * Class GenerateLinkTypeTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_generate_link_type() function.
 */
class GenerateLinkTypeTest extends WP_UnitTestCase {

	/**
	 * Ensure software telemetry uses EDAC_KEY_VALID, not EDACP_KEY_VALID.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_software_param_uses_edac_key_valid_constant() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type function is not available in test environment.' );
		}

		if ( ! defined( 'EDAC_KEY_VALID' ) ) {
			$this->markTestSkipped( 'EDAC_KEY_VALID constant is not defined in test environment.' );
		}

		if ( defined( 'EDACP_KEY_VALID' ) ) {
			$this->markTestSkipped( 'EDACP_KEY_VALID is already defined and cannot be safely overridden for this test.' );
		}

		if ( ! defined( 'EDACP_VERSION' ) ) {
			define( 'EDACP_VERSION', 'test-pro-version' );
		}

		$expected_software = EDAC_KEY_VALID ? 'pro' : 'free';

		// Intentionally set the similarly named constant to the opposite value.
		define( 'EDACP_KEY_VALID', ! EDAC_KEY_VALID );

		$url = edac_generate_link_type();
		parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $query_args );

		$this->assertArrayHasKey( 'software', $query_args );
		$this->assertSame( $expected_software, $query_args['software'] );
	}

	/**
	 * Ensure missing help_id does not raise notices and still builds a help URL.
	 */
	public function test_help_type_without_help_id_does_not_raise_notice() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type function is not available in test environment.' );
		}

		$errors = [];
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- needed to assert no notices are raised by this regression test.
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) {
				$errors[] = $errstr;
				return true;
			}
		);

		$link = edac_generate_link_type(
			[ 'utm_campaign' => 'help-missing-id' ],
			'help',
			[]
		);

		restore_error_handler();

		$this->assertSame( [], $errors, 'Unexpected PHP notices were raised while generating the help link.' );
		$this->assertIsString( $link );
		$this->assertStringStartsWith( 'https://a11ychecker.com/help', $link );
		$this->assertStringContainsString( 'utm_campaign=help-missing-id', $link );
	}
}
