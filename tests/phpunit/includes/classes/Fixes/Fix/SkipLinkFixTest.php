<?php
/**
 * Test class for SkipLinkFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\SkipLinkFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the SkipLinkFix class.
 */
class SkipLinkFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new SkipLinkFix();
		$this->common_setup();
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->common_teardown();
		// Clean up additional options for skip link
		delete_option( 'edac_fix_skip_link_text' );
		delete_option( 'edac_fix_skip_link_element' );
		parent::tearDown();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'skip_link';
	}

	/**
	 * Get the expected type for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_type(): string {
		return 'frontend';
	}

	/**
	 * Get the fix class name.
	 *
	 * @return string
	 */
	protected function get_fix_class_name(): string {
		return SkipLinkFix::class;
	}

	/**
	 * Test skip link has additional configuration fields.
	 *
	 * @return void
	 */
	public function test_skip_link_has_configuration_fields() {
		$fields = $this->fix->get_fields_array();
		
		$this->assertArrayHasKey( 'edac_fix_skip_link_text', $fields );
		$this->assertArrayHasKey( 'edac_fix_skip_link_element', $fields );
	}

	/**
	 * Test frontend data includes custom settings.
	 *
	 * @return void
	 */
	public function test_frontend_data_includes_settings() {
		update_option( 'edac_fix_skip_link', true );
		update_option( 'edac_fix_skip_link_text', 'Custom Skip Text' );
		update_option( 'edac_fix_skip_link_element', '#custom-main' );
		
		$this->fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$skip_data = $data['skip_link'];
		
		$this->assertEquals( 'Custom Skip Text', $skip_data['text'] );
		$this->assertEquals( '#custom-main', $skip_data['element'] );
	}
}