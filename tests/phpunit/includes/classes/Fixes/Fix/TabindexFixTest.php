<?php
/**
 * Test class for TabindexFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\TabindexFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the TabindexFix class.
 */
class TabindexFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new TabindexFix();
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
		return 'remove_tabindex';
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
		return TabindexFix::class;
	}

	/**
	 * TabindexFix uses 'tabindex' key instead of slug for frontend data.
	 * 
	 * @return bool
	 */
	protected function skip_frontend_data_filter_test(): bool {
		return true;
	}

	/**
	 * Test that the frontend data uses correct key.
	 * Note: This fix uses 'tabindex' instead of the slug 'remove_tabindex'.
	 *
	 * @return void
	 */
	public function test_frontend_data_uses_correct_key() {
		update_option( 'edac_fix_remove_tabindex', true );
		$this->fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'tabindex', $data );
		$this->assertTrue( $data['tabindex']['enabled'] );
	}
}
