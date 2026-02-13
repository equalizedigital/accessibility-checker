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
}
