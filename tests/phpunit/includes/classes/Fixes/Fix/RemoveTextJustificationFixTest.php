<?php
/**
 * Test class for RemoveTextJustificationFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\RemoveTextJustificationFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the RemoveTextJustificationFix class.
 */
class RemoveTextJustificationFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new RemoveTextJustificationFix();
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
		return 'remove_text_justification';
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
		return RemoveTextJustificationFix::class;
	}

	/**
	 * Test that default selector is included in frontend fix data.
	 *
	 * @return void
	 */
	public function test_frontend_data_has_default_target_selector() {
		update_option( 'edac_fix_remove_text_justification', true );
		$this->fix->run();

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'remove_text_justification', $data );
		$this->assertArrayHasKey( 'target', $data['remove_text_justification'] );
		$this->assertStringContainsString( 'p, span', $data['remove_text_justification']['target'] );
	}
}
