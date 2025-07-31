<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

use PHPUnit\Framework\TestCase;

/**
 * Test the welcome page partial.
 *
 * @package AccessibilityChecker
 */
class WelcomePagePartialTest extends TestCase {

	/**
	 * Test that the welcome page partial renders some content.
	 */
	public function test_partial_renders() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/welcome-page.php';
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'welcome-page.php should output content' );

		// Test that the output contains all of these expected strings.
		$this->assertStringContainsString( 'Accessibility Checker', $output, 'welcome-page.php should contain the string "Accessibility Checker"' );
		$this->assertStringContainsString( 'meta box below your content', $output, 'welcome-page.php should contain the string "Welcome to Accessibility Checker"' );
		$this->assertStringContainsString( 'get started checking your content', $output, 'welcome-page.php should contain the string "Get Started"' );
	}
}
