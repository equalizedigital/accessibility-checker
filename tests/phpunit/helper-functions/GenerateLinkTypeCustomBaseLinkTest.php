<?php
/**
 * Class GenerateLinkTypeCustomBaseLinkTest
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_generate_link_type custom base link handling.
 */
class GenerateLinkTypeCustomBaseLinkTest extends WP_UnitTestCase {

	/**
	 * Test that a missing base_link does not trigger warnings and falls back to default.
	 */
	public function test_custom_type_without_base_link_falls_back_to_default() {
		// Use a random query arg to ensure the link is generated.
		$link = edac_generate_link_type(
			[ 'utm_campaign' => 'custom-missing-base' ],
			'custom',
			[]
		);

		$this->assertIsString( $link );
		$this->assertStringStartsWith( 'https://equalizedigital.com/accessibility-checker/pricing/', $link );
		$this->assertStringContainsString( 'utm_campaign=custom-missing-base', $link );
	}
}
