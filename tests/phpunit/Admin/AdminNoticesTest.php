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

}
