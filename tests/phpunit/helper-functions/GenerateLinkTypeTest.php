<?php
/**
 * Class GenerateLinkTypeTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_generate_link_type().
 */
class GenerateLinkTypeTest extends WP_UnitTestCase {

	/**
	 * Test help link generation does not emit warnings when help_id is omitted.
	 */
	public function test_help_link_generation_without_help_id_does_not_warn() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		$errors = [];
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in tests to verify warning-free execution.
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				$errors[] = $errstr;
				return true;
			}
		);

		$link = edac_generate_link_type( [], 'help', [] );

		restore_error_handler();

		$this->assertEmpty( $errors, 'Unexpected warning emitted while generating help link.' );
		$this->assertStringContainsString( 'https://a11ychecker.com/help/', $link );
	}

	/**
	 * Test custom link generation falls back safely when base_link is omitted.
	 */
	public function test_custom_link_generation_without_base_link_does_not_warn() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		$errors = [];
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Intentional in tests to verify warning-free execution.
		set_error_handler(
			function ( $errno, $errstr ) use ( &$errors ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				$errors[] = $errstr;
				return true;
			}
		);

		$link = edac_generate_link_type( [], 'custom', [] );

		restore_error_handler();

		$this->assertEmpty( $errors, 'Unexpected warning emitted while generating custom link without base_link.' );
		$this->assertStringContainsString( 'https://equalizedigital.com/accessibility-checker/pricing/', $link );
	}
}
