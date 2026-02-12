<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

/**
 * Test the admin fixes page partial.
 *
 * @package AccessibilityChecker
 */
class AdminFixesPagePartialTest extends WP_UnitTestCase {

	/**
	 * Test that the admin fixes page partial renders some content.
	 */
	public function test_partial_renders() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/admin-page/fixes-page.php';
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'admin-page/fixes-page.php should output content' );
	}

	/**
	 * Test that the settings saved notice appears when transient is set.
	 */
	public function test_partial_shows_settings_saved_notice_when_transient_set() {
		set_transient( 'edac_fixes_settings_saved', true, 60 );

		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/admin-page/fixes-page.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'notice notice-warning is-dismissible', $output );
		$this->assertStringContainsString( 'Settings Saved', $output );

		// Transient should be deleted after rendering.
		$this->assertFalse( get_transient( 'edac_fixes_settings_saved' ) );
	}

	/**
	 * Test that the pro callout is shown when EDAC_KEY_VALID is false.
	 */
	public function test_partial_shows_pro_callout_when_key_not_valid() {
		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/admin-page/fixes-page.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( 'pro-callout-wrapper', $output );
		$this->assertStringContainsString( 'edac-show-pro-callout', $output );
	}
}
