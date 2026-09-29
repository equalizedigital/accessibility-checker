<?php
/**
 * Accessibility Checker
 *
 * @package AccessibilityChecker
 */

/**
 * Test the settings page partial.
 *
 * @package AccessibilityChecker
 */
class SettingsPagePartialTest extends WP_UnitTestCase {

	/**
	 * Prepare settings errors for each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_settings_errors'] = [];
		$GLOBALS['title']              = 'Accessibility Checker Settings';
	}

	/**
	 * Clean up settings errors after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['wp_settings_errors'], $GLOBALS['title'] );

		parent::tearDown();
	}

	/**
	 * Test that the settings page renders the standard saved notice.
	 */
	public function testPartialRendersSettingsSavedNotice() {
		add_settings_error(
			'general',
			'settings_updated',
			'Settings saved.',
			'success'
		);

		ob_start();
		include dirname( __DIR__, 3 ) . '/partials/settings-page.php';
		$output = ob_get_clean();

		$this->assertStringContainsString( "id='setting-error-settings_updated'", $output );
		$this->assertStringContainsString( 'notice-success', $output );
		$this->assertStringContainsString( 'Settings saved.', $output );
	}
}
