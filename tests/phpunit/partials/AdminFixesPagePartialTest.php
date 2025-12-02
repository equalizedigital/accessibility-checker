<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the admin fixes page partial.
 *
 * @package AccessibilityChecker
 */
class AdminFixesPagePartialTest extends TestCase {

	/**
	 * Test that the admin fixes page partial renders some content.
	 */
	public function test_partial_renders() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/admin-page/fixes-page.php';
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'admin-page/fixes-page.php should output content' );
	}
}
