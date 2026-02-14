<?php
/**
 * Class GenerateLinkTypeTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test case for edac_generate_link_type function.
 */
class GenerateLinkTypeTest extends WP_UnitTestCase {

	/**
	 * Clean up options after each test.
	 */
	public function tearDown(): void {
		delete_option( 'edac_activation_date' );
		parent::tearDown();
	}

	/**
	 * Tests that invalid activation dates do not throw errors.
	 */
	public function test_generate_link_type_with_invalid_activation_date() {
		update_option( 'edac_activation_date', 'not-a-date' );

		$link = edac_generate_link_type();

		$query = wp_parse_url( $link, PHP_URL_QUERY );
		parse_str( $query, $params );

		$this->assertArrayHasKey( 'days_active', $params );
		$this->assertSame( '0', $params['days_active'] );
	}

	/**
	 * Tests that non-string activation date values do not throw errors.
	 */
	public function test_generate_link_type_with_non_string_activation_date() {
		update_option( 'edac_activation_date', [ 'bad' => 'value' ] );

		$link = edac_generate_link_type();

		$query = wp_parse_url( $link, PHP_URL_QUERY );
		parse_str( $query, $params );

		$this->assertArrayHasKey( 'days_active', $params );
		$this->assertSame( '0', $params['days_active'] );
	}
}
