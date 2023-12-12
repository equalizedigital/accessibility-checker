<?php
/**
 * Class EDACAdminNoticesTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin_Notices;
/**
 * Admin Notices test case.
 */
class EDACAdminNoticesTest extends WP_UnitTestCase {
	
	private $admin_notices;

	protected function setUp(): void {
		$this->admin_notices = new Admin_Notices();
	}

	/**
	 * Test that the edac_get_black_friday_message function exists.
	 */
	public function test_edac_get_black_friday_message_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_get_black_friday_message'),
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
			method_exists($this->admin_notices, 'edac_get_gaad_promo_message'),
			'Class does not have method edac_get_gaad_promo_message'
		);
	}

	/**
	 * Test that the edac_get_gaad_promo_message function returns a string.
	 */
	public function test_edac_get_gaad_promo_message_returns_string() {
		$this->assertIsString($this->admin_notices->edac_get_gaad_promo_message());
	}

	/**
	 * Test that the edac_get_gaad_promo_message function contains the expected promotional message.
	 */
	public function test_edac_get_gaad_promo_message_contains_promo_message() {
		$message = $this->admin_notices->edac_get_gaad_promo_message();
		$this->assertStringContainsString( '🎉 Get 30% off Accessibility Checker Pro in honor of Global Accessibility Awareness Day! 🎉', $message );
		$this->assertStringContainsString( 'Use coupon code GAAD23 from May 18th-May 25th to get access to full-site scanning and other pro features at a special discount.', $message );
		$this->assertStringContainsString( 'https://my.equalizedigital.com/support/pre-sale-questions/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=GAAD23', $message );
		$this->assertStringContainsString( 'https://equalizedigital.com/accessibility-checker/pricing/?utm_source=accessibility-checker&utm_medium=software&utm_campaign=GAAD23', $message );
	}

	/**
	 * Test that the edac_password_protected_notice_text function exists.
	 */
	public function test_edac_password_protected_notice_text_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_password_protected_notice_text'),
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
		$this->assertStringContainsString( 'To scan this website for accessibility problems either remove the password protection or upgrade to pro.', $message );
		$this->assertStringContainsString( 'Scan results may be stored from a previous scan.', $message );
	}

}
