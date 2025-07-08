<?php
/**
 * PHPUnit tests for the Admin_Footer_Text class.
 *
 * @package Accessibility_Checker\Tests
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Admin\Admin_Footer_Text;

/**
 * Class Admin_Footer_Text_Test
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\Admin_Footer_Text
 */
class AdminFooterTextTest extends WP_UnitTestCase {

	/**
	 * Instance of the Admin_Footer_Text class.
	 *
	 * @var Admin_Footer_Text $admin_footer_text.
	 */
	private $admin_footer_text;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->admin_footer_text = new Admin_Footer_Text();
		
		// Mock edac_link_wrapper function if it doesn't exist.
		if ( ! function_exists( 'edac_link_wrapper' ) ) {
			/**
			 * Mock edac_link_wrapper for testing.
			 *
			 * This is a simplified mock that intentionally ignores most parameters
			 * and only returns the base URL for testing purposes.
			 *
			 * @param string $url URL.
			 * @param string $campaign Campaign - intentionally unused in mock.
			 * @param string $content Content - intentionally unused in mock.
			 * @param bool   $directly_echo Echo - intentionally unused in mock.
			 * @return string
			 */
			function edac_link_wrapper( $url, $campaign = '', $content = '', $directly_echo = true ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Mock function intentionally ignores parameters
				// Simple mock that ignores tracking parameters and returns the URL.
				return $url;
			}
		}
	}

	/**
	 * Test instantiation of Admin_Footer_Text class.
	 */
	public function test_can_instantiate_class() {
		$this->assertInstanceOf( Admin_Footer_Text::class, $this->admin_footer_text );
	}

	/**
	 * Test that init() adds the admin_footer_text filter.
	 */
	public function test_init_adds_filter() {
		$this->admin_footer_text->init();
		$this->assertTrue( has_filter( 'admin_footer_text', [ $this->admin_footer_text, 'filter_footer_text' ] ) !== false );
	}

	/**
	 * Test filter_footer_text() returns original text when not on settings page.
	 */
	public function test_filter_footer_text_returns_original_on_non_settings_page() {
		// Ensure we're not on a settings page.
		unset( $_GET['page'] );
		
		$original_text = 'Original footer text';
		$result        = $this->admin_footer_text->filter_footer_text( $original_text );
		$this->assertEquals( $original_text, $result );
	}

	/**
	 * Test filter_footer_text() with accessibility checker page but no pro.
	 */
	public function test_filter_footer_text_returns_unlock_pro_message() {
		// Set up to be on settings page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker_settings';

		$result = $this->admin_footer_text->filter_footer_text( 'Original text' );
		$this->assertStringContainsString( 'Want to do more with Accessibility Checker?', $result );
		$this->assertStringContainsString( 'Unlock Pro Features', $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test is_settings_page() returns false when not in admin.
	 */
	public function test_is_settings_page_returns_false_when_not_admin() {
		// Ensure we're not in admin context.
		unset( $_GET['page'] );

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertFalse( $result );
	}

	/**
	 * Test is_settings_page() returns false when page parameter is not set.
	 */
	public function test_is_settings_page_returns_false_when_no_page_param() {
		// Ensure we're in admin context but no page param.
		set_current_screen( 'dashboard' );
		unset( $_GET['page'] );

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertFalse( $result );
	}

	/**
	 * Test is_settings_page() returns true for accessibility checker pages.
	 */
	public function test_is_settings_page_returns_true_for_accessibility_checker_page() {
		// Set up admin context and page parameter.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker_settings';

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertTrue( $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test is_settings_page() returns true for other accessibility checker pages.
	 */
	public function test_is_settings_page_returns_true_for_other_accessibility_checker_pages() {
		// Set up admin context with different accessibility checker page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker';

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertTrue( $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test is_settings_page() returns false for non-accessibility checker pages.
	 */
	public function test_is_settings_page_returns_false_for_other_pages() {
		// Set up admin context with different page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'other_plugin_settings';

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertFalse( $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test is_pro_active() returns false when constants are not defined.
	 */
	public function test_is_pro_active_returns_false_when_constants_not_defined() {
		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_pro_active' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->admin_footer_text );
		$this->assertFalse( $result );
	}

	/**
	 * Test output contains proper accessibility attributes.
	 */
	public function test_output_contains_accessibility_attributes() {
		// Set up to be on settings page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker_settings';

		$result = $this->admin_footer_text->filter_footer_text( 'Original text' );
		$this->assertStringContainsString( 'aria-label=', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
		$this->assertStringContainsString( 'opens in new window', $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test that page parameter is properly sanitized.
	 */
	public function test_page_parameter_sanitization() {
		// Set up admin context with potentially malicious page param.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker<script>alert("xss")</script>';

		$reflection = new \ReflectionClass( $this->admin_footer_text );
		$method     = $reflection->getMethod( 'is_settings_page' );
		$method->setAccessible( true );

		// Should still return true because it contains 'accessibility_checker'.
		// But the script tags should be sanitized internally.
		$result = $method->invoke( $this->admin_footer_text );
		$this->assertTrue( $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test filter integration with WordPress hooks.
	 */
	public function test_filter_integration() {
		$this->admin_footer_text->init();
		
		// Set up to be on settings page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker_settings';

		// Apply the filter as WordPress would.
		$result = apply_filters( 'admin_footer_text', 'Original footer text' );
		
		$this->assertStringContainsString( 'Want to do more with Accessibility Checker?', $result );
		$this->assertNotEquals( 'Original footer text', $result );

		// Clean up.
		unset( $_GET['page'] );
	}

	/**
	 * Test that output is properly escaped.
	 */
	public function test_output_is_escaped() {
		// Set up to be on settings page.
		set_current_screen( 'admin' );
		$_GET['page'] = 'accessibility_checker_settings';

		$result = $this->admin_footer_text->filter_footer_text( 'Original text' );
		
		// Should contain proper HTML structure.
		$this->assertStringContainsString( '<a href=', $result );
		$this->assertStringContainsString( 'target="_blank"', $result );
		
		// Should not contain unescaped output.
		$this->assertStringNotContainsString( '&amp;amp;', $result );

		// Clean up.
		unset( $_GET['page'] );
	}
}
