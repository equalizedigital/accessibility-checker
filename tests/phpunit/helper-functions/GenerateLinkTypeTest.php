<?php
/**
 * Tests for the edac_generate_link_type helper.
 *
 * @package Accessibility_Checker
 * @since 1.37.0
 */

/**
 * Tests for edac_generate_link_type.
 *
 * @covers ::edac_generate_link_type
 * @since 1.37.0
 */
class GenerateLinkTypeTest extends WP_UnitTestCase {

	/**
	 * Verifies help links can be generated without a help_id.
	 *
	 * This regression test converts warnings/notices into exceptions so an
	 * undefined index for help_id fails loudly.
	 */
	public function test_edac_generate_link_type_help_without_help_id(): void {
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s' ) );

		set_error_handler(
			static function ( $severity, $message, $file, $line ) {
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

		$link = edac_generate_link_type(
			[],
			'help',
			[
				'help_id' => '/rule-name',
			]
		);

		delete_option( 'edac_activation_date' );

		$this->assertSame( '/help/rule-name/', wp_parse_url( $link, PHP_URL_PATH ) );
	}
}
