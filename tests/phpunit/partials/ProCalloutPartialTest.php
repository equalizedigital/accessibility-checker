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
}
