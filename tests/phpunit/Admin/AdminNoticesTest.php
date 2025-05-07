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
	 * Test that the edac_get_black_friday_message function contains the expected promotional message.
	 */
	public function test_edac_get_black_friday_message_contains_promo_message() {
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Black Friday special!', $message );
		$this->assertStringContainsString( 'Upgrade to a paid version of Accessibility Checker', $message );
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
	 * Test that the edac_get_gaad_promo_message function contains the expected promotional message.
	 */
	public function test_edac_get_gaad_promo_message_contains_promo_message() {
		$message = $this->admin_notices->edac_get_gaad_promo_message();
		$this->assertStringContainsString( 'Accessibility Checker Pro in honor of Global Accessibility Awareness Day', $message );
		$this->assertStringContainsString( 'get access to full-site scanning', $message );
		$this->assertStringContainsString( 'https://equalizedigital.com/contact/', $message );
		$this->assertStringContainsString( 'https://equalizedigital.com/accessibility-checker/pricing/', $message );
	}

	/**
	 * Test that the edac_password_protected_notice_text function exists.
	 */
	public function test_edac_password_protected_notice_text_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_password_protected_notice_text' ),
			'Class does not have method edac_password_protected_notice_text'
		);
	}

	/**
	 * Test that the edac_password_protected_notice_text function returns a string.
	 */
	public function test_edac_password_protected_notice_text_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_password_protected_notice_text() );
	}

	/**
	 * Test that the edac_password_protected_notice_text function contains the expected notice message.
	 */
	public function test_edac_password_protected_notice_text_contains_notice_message() {
		$message = $this->admin_notices->edac_password_protected_notice_text();
		$this->assertStringContainsString( 'Whoops! It looks like your website is currently password protected.', $message );
		$this->assertStringContainsString( 'The free version of Accessibility Checker can only scan live websites.', $message );
		$this->assertStringContainsString( 'To scan this website for accessibility problems either remove the password protection or <a href="https://equalizedigital.com/accessibility-checker/pricing/" target="_blank" aria-label="Upgrade to accessibility checker pro. Opens in a new window.">upgrade to pro</a>.', $message );
		$this->assertStringContainsString( 'Scan results may be stored from a previous scan.', $message );
	}
}
