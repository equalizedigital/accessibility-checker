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
		parent::setUp();
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
	 * Set the stored license state the Black Friday segments are derived from.
	 *
	 * @param string $status The edacp_license_status value.
	 * @param string $plan   The plan stored in the license metadata, or an empty string for none.
	 */
	private function set_license_state( $status, $plan = '' ) {
		update_option( 'edacp_license_status', $status );
		if ( '' !== $plan ) {
			update_option( 'edac_license_metadata', [ 'plan' => $plan ] );
		}
	}
	/**
	 * Test that each license state maps to the right Black Friday segment.
	 */
	public function test_black_friday_segments() {
		$this->assertSame( 'free', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'expired' );
		$this->assertSame( 'lapsed', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'professional' );
		$this->assertSame( 'professional', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'small-business' );
		$this->assertSame( 'smb', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'agency' );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'enterprise' );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'small-business-plus' );
		$this->assertSame( 'smbplus', $this->admin_notices->edac_get_black_friday_segment() );
		// A valid license with no recorded plan yet, or an unrecognised one, gets no notice.
		$this->set_license_state( 'valid', 'unknown' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_segment() );
		delete_option( 'edac_license_metadata' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_segment() );
	}
	/**
	 * Test the copy and tracking for each segment, and that agency shows no notice.
	 */
	public function test_black_friday_message_per_segment() {
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'save 20% and get a free course', $message );
		$this->assertStringContainsString( 'BlackFriday26-free-presale', $message );
		$this->assertStringNotContainsString( 'pre-sale-questions/?discount', $message );
		$this->assertStringContainsString( 'BlackFriday26-free-pricing', $message );
		$this->assertStringContainsString( 'discount=BF2674B96A3C', $message );
		$this->set_license_state( 'expired' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Welcome back', $message );
		$this->assertStringContainsString( 'BlackFriday26-lapsed-renew', $message );
		$this->assertStringContainsString( 'discount=BF2674B96A3C', $message );
		$this->set_license_state( 'valid', 'professional' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Small Business', $message );
		$this->assertStringContainsString( 'BlackFriday26-pro-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF26856B22FF', $message );
		$this->set_license_state( 'valid', 'small-business' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Upgrade &amp; Save 25%', $message );
		$this->assertStringContainsString( 'BlackFriday26-smb-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF2622E6189C', $message );
		$this->set_license_state( 'valid', 'small-business-plus' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'upgrade to Agency', $message );
		$this->assertStringContainsString( 'BlackFriday26-smbplus-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF2622E6189C', $message );
		$this->set_license_state( 'valid', 'agency' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_message() );
	}
	/**
	 * Test that removing admin notices does not error when the current screen is unavailable.
	 */
	public function test_edac_remove_admin_notices_handles_missing_screen() {
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
	 * Test that GAAD pre-sale message method exists.
	 */
	public function test_edac_get_gaad_presale_message_exists() {
		$this->assertTrue( method_exists( $this->admin_notices, 'edac_get_gaad_presale_message' ) );
	}
	/**
	 * Test that GAAD pre-sale message method returns a string.
	 */
	public function test_edac_get_gaad_presale_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_gaad_presale_message() );
	}
	/**
	 * Test pre-sale copy for non-pro users.
	 */
	public function test_edac_get_gaad_presale_message_non_pro_copy() {
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on Accessibility Checker Pro', $message );
		$this->assertStringContainsString( '3 days only', $message );
		$this->assertStringContainsString( 'View Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/accessibility-checker/pricing/', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringNotContainsString( 'View Course Pricing', $message );
	}
	/**
	 * Test pre-sale copy for pro users.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_presale_message_pro_copy() {
		if ( defined( 'EDAC_KEY_VALID' ) || defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test Pro-message branch: EDAC_KEY_VALID or EDACP_VERSION is already defined in this environment.' );
		}
		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on accessibility courses and ArchiveWP', $message );
		$this->assertStringContainsString( '3 days only', $message );
		$this->assertStringContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringContainsString( 'View Course Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/archivewp/', $message );
		$this->assertStringContainsString( 'equalizedigital.com/learn/courses/', $message );
	}
	/**
	 * Test that pro plugin without valid key gets non-pro pre-sale copy.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_presale_message_pro_without_valid_key_shows_non_pro_copy() {
		define( 'EDACP_VERSION', '1.0.0' );
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'View Pricing', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
	}
	/**
	 * Test that GAAD sale message method exists.
	 */
	public function test_edac_get_gaad_sale_message_exists() {
		$this->assertTrue( method_exists( $this->admin_notices, 'edac_get_gaad_sale_message' ) );
	}
	/**
	 * Test that GAAD sale message method returns a string.
	 */
	public function test_edac_get_gaad_sale_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_gaad_sale_message() );
	}
	/**
	 * Test sale copy for non-pro users.
	 */
	public function test_edac_get_gaad_sale_message_non_pro_copy() {
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( '3 days only: Save 15% when you upgrade to Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'Limited-time offer', $message );
		$this->assertStringContainsString( 'Ends May 22nd', $message );
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringContainsString( 'equalizedigital.com/accessibility-checker/pricing/?discount=GAAD2026', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringNotContainsString( 'View Course Pricing', $message );
	}
	/**
	 * Test sale copy for pro users.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_sale_message_pro_copy() {
		if ( defined( 'EDAC_KEY_VALID' ) || defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test Pro-message branch: EDAC_KEY_VALID or EDACP_VERSION is already defined in this environment.' );
		}
		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Save 15% on accessibility courses and ArchiveWP', $message );
		$this->assertStringContainsString( 'Limited-time offer', $message );
		$this->assertStringContainsString( 'Ends May 22nd', $message );
		$this->assertStringContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringContainsString( 'View Course Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/archivewp/?discount=GAAD2026', $message );
		$this->assertStringContainsString( 'equalizedigital.com/learn/courses/?discount=GAAD2026', $message );
	}
	/**
	 * Test that pro plugin without valid key gets non-pro sale copy.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_sale_message_pro_without_valid_key_shows_non_pro_copy() {
		define( 'EDACP_VERSION', '1.0.0' );
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( '3 days only: Save 15% when you upgrade to Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
	}
	/**
	 * Test that GAAD AJAX handlers are registered.
	 */
	public function test_gaad_ajax_hooks_are_registered() {
		$this->admin_notices->init_hooks();
		$this->assertSame( 10, has_action( 'wp_ajax_edac_gaad_presale_notice_ajax', [ $this->admin_notices, 'edac_gaad_presale_notice_ajax' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_edac_gaad_sale_notice_ajax', [ $this->admin_notices, 'edac_gaad_sale_notice_ajax' ] ) );
	}
}
