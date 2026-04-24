<?php
/**
 * Class EDACAdminNoticesTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Admin_Notices;

/**
 * Admin Notices test case.
 */
class AdminNoticesTest extends WP_UnitTestCase {

	/**
	 * Instance of the Admin_Notices class.
	 *
	 * @var Admin_Notices $admin_notices.
	 */
	private $admin_notices;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->admin_notices = new Admin_Notices();
	}

	/**
	 * Test that the edac_get_black_friday_message function exists.
	 */
	public function test_edac_get_black_friday_message_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_get_black_friday_message' ),
			'Class does not have method edac_get_black_friday_message'
		);
	}

	/**
	 * Test that the edac_get_black_friday_message function returns a string.
	 */
	public function test_edac_get_black_friday_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_black_friday_message() );
	}

	/**
	 * Test that removing admin notices does not error when the current screen is unavailable.
	 */
	public function test_edac_remove_admin_notices_handles_missing_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			$this->markTestSkipped( 'get_current_screen is not available in this test environment.' );
		}

		global $current_screen;
		$previous_screen = $current_screen ?? null;
		$current_screen  = null;

		try {
			$this->admin_notices->edac_remove_admin_notices();
			$this->assertTrue( true );
		} finally {
			$current_screen = $previous_screen;
		}
	}

	/**
	 * Test that the edac_get_black_friday_message function contains the expected promotional message.
	 */
	public function test_edac_get_black_friday_message_contains_promo_message() {
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Black Friday special!', $message );
		$this->assertStringContainsString( 'Upgrade to a paid version of Accessibility Checker', $message );
		$this->assertStringContainsString( 'November 24th to December 3rd', $message );
		$this->assertStringContainsString( 'BlackFriday25', $message );
		$this->assertStringContainsString( '30% off', $message );
	}

	/**
	 * Test that the edac_get_gaad_promo_message function exists.
	 */
	public function test_edac_get_gaad_promo_message_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_get_gaad_promo_message' ),
			'Class does not have method edac_get_gaad_promo_message'
		);
	}

	/**
	 * Test that the edac_get_gaad_promo_message function returns a string.
	 */
	public function test_edac_get_gaad_promo_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_gaad_promo_message() );
	}

	/**
	 * Test that the edac_get_gaad_promo_message function contains the expected non-Pro promotional message.
	 */
	public function test_edac_get_gaad_promo_message_contains_promo_message() {
		$message = $this->admin_notices->edac_get_gaad_promo_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'GAAD2026', $message );
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringContainsString( 'https://equalizedigital.com/accessibility-checker/pricing/', $message );
	}

	/**
	 * Test that the non-Pro GAAD promo message does not contain Pro-only content.
	 */
	public function test_edac_get_gaad_promo_message_non_pro_does_not_contain_pro_content() {
		$message = $this->admin_notices->edac_get_gaad_promo_message();
		$this->assertStringNotContainsString( 'Grab the Deal Before It Ends', $message );
		$this->assertStringNotContainsString( 'equalizedigital.com/learn/courses/', $message );
		$this->assertStringNotContainsString( 'equalizedigital.com/archivewp/', $message );
	}

	/**
	 * Test that the Pro GAAD promo message is shown when EDACP_VERSION is defined and edac_is_pro() is true.
	 *
	 * Note: EDAC_KEY_VALID is defined by the plugin bootstrap in the test environment, so this test
	 * can only run in environments where both constants are not yet defined (e.g. Pro plugin installed
	 * with a valid key). It mirrors the skip pattern used by PluginActionLinksUndefinedKeyTest.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_promo_message_pro_contains_pro_content() {
		if ( defined( 'EDAC_KEY_VALID' ) || defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test Pro-message branch: EDAC_KEY_VALID or EDACP_VERSION is already defined in this environment.' );
		}

		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );

		$message = $this->admin_notices->edac_get_gaad_promo_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'accessibility courses', $message );
		$this->assertStringContainsString( 'equalizedigital.com/learn/courses/', $message );
		$this->assertStringContainsString( 'ArchiveWP', $message );
		$this->assertStringContainsString( 'equalizedigital.com/archivewp/', $message );
		$this->assertStringContainsString( 'Grab the Deal Before It Ends', $message );
		$this->assertStringContainsString( 'GAAD2026', $message );
	}

	/**
	 * Test that the Pro GAAD promo message is NOT shown when EDACP_VERSION is defined but edac_is_pro() is false (no valid key).
	 *
	 * The plugin bootstrap always defines EDAC_KEY_VALID as false in the test environment, so we only
	 * need to define EDACP_VERSION to exercise the "Pro plugin installed, but no valid license" path.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_promo_message_pro_version_without_valid_key_shows_non_pro_message() {
		if ( defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test this branch: EDACP_VERSION is already defined in this environment.' );
		}

		// EDAC_KEY_VALID is already defined as false by the plugin bootstrap (get_option check).
		// Defining EDACP_VERSION simulates Pro plugin installed without a valid key.
		define( 'EDACP_VERSION', '1.0.0' );

		$message = $this->admin_notices->edac_get_gaad_promo_message();
		// Should fall back to the non-Pro (upgrade) message.
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringNotContainsString( 'Grab the Deal Before It Ends', $message );
	}
}
