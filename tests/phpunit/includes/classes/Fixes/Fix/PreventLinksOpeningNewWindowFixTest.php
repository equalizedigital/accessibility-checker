<?php
/**
 * Test class for PreventLinksOpeningNewWindowFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\PreventLinksOpeningNewWindowFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the PreventLinksOpeningNewWindowFix class.
 */
class PreventLinksOpeningNewWindowFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new PreventLinksOpeningNewWindowFix();
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
		return 'prevent_links_opening_new_windows';
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
		return PreventLinksOpeningNewWindowFix::class;
	}

	/**
	 * Test link modification functionality.
	 *
	 * @return void
	 */
	public function test_link_target_modification() {
		update_option( 'edac_fix_prevent_links_opening_new_windows', true );
		$this->fix->run();
		
		// Test frontend data filter.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'prevent_links_opening_new_windows', $data );
		$this->assertTrue( $data['prevent_links_opening_new_windows']['enabled'] );
	}
}
