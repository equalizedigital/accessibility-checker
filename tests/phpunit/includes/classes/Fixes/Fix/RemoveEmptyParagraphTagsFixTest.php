<?php
/**
 * Test class for RemoveEmptyParagraphTagsFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\RemoveEmptyParagraphTagsFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the RemoveEmptyParagraphTagsFix class.
 */
class RemoveEmptyParagraphTagsFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new RemoveEmptyParagraphTagsFix();
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
		return 'remove_empty_paragraph_tags';
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
		return RemoveEmptyParagraphTagsFix::class;
	}

	/**
	 * Test that frontend data includes this fix when enabled.
	 *
	 * @return void
	 */
	public function test_frontend_data_includes_fix_when_enabled() {
		update_option( 'edac_fix_remove_empty_paragraph_tags', true );
		$this->fix->run();

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'remove_empty_paragraph_tags', $data );
		$this->assertTrue( $data['remove_empty_paragraph_tags']['enabled'] );
	}
}
