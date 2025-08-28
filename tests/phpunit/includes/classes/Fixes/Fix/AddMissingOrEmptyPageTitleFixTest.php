<?php
/**
 * Test class for AddMissingOrEmptyPageTitleFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddMissingOrEmptyPageTitleFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the AddMissingOrEmptyPageTitleFix class.
 */
class AddMissingOrEmptyPageTitleFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new AddMissingOrEmptyPageTitleFix();
		$this->common_setup();
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->common_teardown();
		parent::tearDown();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'missing_or_empty_page_title';
	}

	/**
	 * Get the expected type for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_type(): string {
		return 'none';
	}

	/**
	 * Get the fix class name.
	 *
	 * @return string
	 */
	protected function get_fix_class_name(): string {
		return AddMissingOrEmptyPageTitleFix::class;
	}

	/**
	 * This fix doesn't use frontend data filter.
	 *
	 * @return bool
	 */
	protected function skip_frontend_data_filter_test(): bool {
		return true;
	}

	/**
	 * Override test since this fix is pro-only and doesn't run.
	 *
	 * @return void
	 */
	public function test_run_when_option_enabled() {
		// Set first option to true.
		update_option( 'edac_fix_add_missing_or_empty_page_title', true );
		
		// Pro-only fix doesn't do anything in run method.
		$result = $this->fix->run();
		$this->assertNull( $result );
	}

	/**
	 * Test that run method does nothing.
	 * This fix is pro-only and doesn't implement actual functionality.
	 *
	 * @return void
	 */
	public function test_run_does_nothing() {
		$this->assertNull( $this->fix->run() );
	}

	/**
	 * Test pro upsell is enabled by default.
	 *
	 * @return void
	 */
	public function test_fields_show_pro_upsell() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_missing_or_empty_page_title'];
		$this->assertTrue( $field['upsell'] );
	}
}
