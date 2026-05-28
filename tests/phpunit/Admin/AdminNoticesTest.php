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
		if ( defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test this branch: EDACP_VERSION is already defined in this environment.' );
		}
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
		if ( defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test this branch: EDACP_VERSION is already defined in this environment.' );
		}
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
