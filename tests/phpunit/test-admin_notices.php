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

    public function test_edac_get_black_friday_message_exists() {
        $this->assertTrue(
            method_exists( $this->admin_notices, 'edac_get_black_friday_message'),
            'Class does not have method edac_get_black_friday_message'
        );
    }

    public function test_edac_get_black_friday_message_returns_string() {
        $this->assertIsString( $this->admin_notices->edac_get_black_friday_message() );
    }

    public function test_edac_get_black_friday_message_contains_promo_message() {
        $message = $this->admin_notices->edac_get_black_friday_message();
        $this->assertStringContainsString( 'Black Friday special!', $message );
        $this->assertStringContainsString( 'Upgrade to a paid version of Accessibility Checker', $message );
    }
}
