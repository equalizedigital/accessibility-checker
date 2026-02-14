<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the pro callout partial.
 */
class ProCalloutPartialTest extends TestCase {

	/**
	 * Test that the pro callout partial renders some content.
	 */
	public function test_partial_renders() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/pro-callout.php';
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'pro-callout.php should output content' );
	}

	/**
	 * Test that rendered pro callout links use underscore UTM keys.
	 */
	public function test_partial_uses_underscore_utm_keys() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/pro-callout.php';
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'utm_campaign=pro-callout', $output );
		$this->assertStringContainsString( 'utm_content=get-pro', $output );
		$this->assertStringNotContainsString( 'utm-campaign=pro-callout', $output );
		$this->assertStringNotContainsString( 'utm-content=get-pro', $output );
	}
}
