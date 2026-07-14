<?php
/**
 * Tests for options-page source safety checks.
 *
 * @package Accessibility_Checker
 */

/**
 * Verify options page source keeps EDAC_KEY_VALID usage guarded.
 */
class OptionsPageSourceTest extends WP_UnitTestCase {

	/**
	 * Test that EDAC_KEY_VALID is checked with defined() before usage.
	 *
	 * @return void
	 */
	public function test_post_types_cb_guards_edac_key_valid_constant() {
		$source = file_get_contents( EDAC_PLUGIN_DIR . 'includes/options-page.php' );

		$this->assertNotFalse( $source, 'Failed to read includes/options-page.php' );
		$this->assertStringContainsString(
			"defined( 'EDAC_KEY_VALID' ) && false === EDAC_KEY_VALID",
			$source
		);
	}
}
