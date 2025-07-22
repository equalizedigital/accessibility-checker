<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the custom meta box partial.
 *
 * @package AccessibilityChecker
 */
class CustomMetaBoxPartialTest extends TestCase {

	/**
	 * Test that the custom meta box partial renders some content.
	 */
	public function test_partial_renders() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/custom-meta-box.php';
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'custom-meta-box.php should output content' );
	}
}
