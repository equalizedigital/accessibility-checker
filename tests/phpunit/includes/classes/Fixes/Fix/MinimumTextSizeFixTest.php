<?php
/**
 * Test class for MinimumTextSizeFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\MinimumTextSizeFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the MinimumTextSizeFix class.
 */
class MinimumTextSizeFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new MinimumTextSizeFix();
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
		return 'minimum_text_size';
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
		return MinimumTextSizeFix::class;
	}

	/**
	 * Get the options used by this fix.
	 *
	 * @return array
	 */
	protected function get_fix_option_names(): array {
		return [
			'edac_fix_minimum_text_size',
			'edac_fix_minimum_text_size_px',
		];
	}

	/**
	 * Test that minimum size setting is sanitized.
	 *
	 * @return void
	 */
	public function test_frontend_data_enforces_minimum_floor() {
		update_option( 'edac_fix_minimum_text_size', true );
		update_option( 'edac_fix_minimum_text_size_px', 4 );

		$this->fix->run();

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'minimum_text_size', $data );
		$this->assertEquals( 10, $data['minimum_text_size']['min_size'] );
	}
}
