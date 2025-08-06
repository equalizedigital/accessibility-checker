<?php
/**
 * Test class for FocusOutlineFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\FocusOutlineFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the FocusOutlineFix class.
 */
class FocusOutlineFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new FocusOutlineFix();
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
		return 'focus_outline';
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
		return FocusOutlineFix::class;
	}

	/**
	 * FocusOutlineFix doesn't use frontend data filter.
	 *
	 * @return bool
	 */
	protected function skip_frontend_data_filter_test(): bool {
		return true;
	}

	/**
	 * Test that CSS is injected when enabled.
	 *
	 * @return void
	 */
	public function test_run_adds_css_when_enabled() {
		update_option( 'edac_fix_focus_outline', true );
		
		$this->fix->run();
		
		$this->assertTrue( has_action( 'wp_head', [ $this->fix, 'css' ] ) !== false );
	}
}
