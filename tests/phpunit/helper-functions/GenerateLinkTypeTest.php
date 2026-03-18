<?php
/**
 * Tests for the edac_generate_link_type helper.
 *
 * @package Accessibility_Checker
 * @since 1.37.0
 */

namespace EqualizeDigital\AccessibilityChecker;

use ErrorException;

/**
 * Tests for edac_generate_link_type.
 *
 * @covers ::edac_generate_link_type
 * @since 1.37.0
 */
class GenerateLinkTypeTest extends \WP_UnitTestCase {

	/**
	 * Ensure software telemetry uses EDAC_KEY_VALID, not EDACP_KEY_VALID.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_software_param_uses_edac_key_valid_constant(): void {
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
	 * Verifies help links can be generated without a help_id.
	 *
	 * This regression test converts warnings/notices into exceptions so an
	 * undefined index for help_id fails loudly.
	 */
	public function test_edac_generate_link_type_help_without_help_id(): void {
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			static function ( $severity, $message, $file, $line ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- test-only handler, not output.
				throw new ErrorException( $message, 0, $severity, $file, $line );
			}
		);

		try {
			$link = edac_generate_link_type( [], 'help' );
		} finally {
			restore_error_handler();
			delete_option( 'edac_activation_date' );
		}

		$this->assertSame( '/help/', wp_parse_url( $link, PHP_URL_PATH ) );
	}

	/**
	 * Verifies help links include the requested help path suffix.
	 */
	public function test_edac_generate_link_type_help_with_help_id(): void {
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );

		try {
			$link = edac_generate_link_type(
				[],
				'help',
				[
					'help_id' => '/rule-name',
				]
			);

			$this->assertSame( '/help/rule-name/', wp_parse_url( $link, PHP_URL_PATH ) );
		} finally {
			delete_option( 'edac_activation_date' );
		}
	}
}
