<?php
/**
 * Test class for IframeMissingTitleFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\IframeMissingTitleFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the IframeMissingTitleFix class.
 */
class IframeMissingTitleFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test instance.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new IframeMissingTitleFix();
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
	 * Get expected slug.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'iframe_missing_title';
	}

	/**
	 * Get expected type.
	 *
	 * @return string
	 */
	protected function get_expected_type(): string {
		return 'frontend';
	}

	/**
	 * Get fix class name.
	 *
	 * @return string
	 */
	protected function get_fix_class_name(): string {
		return IframeMissingTitleFix::class;
	}

	/**
	 * Test fallback title value is added to frontend data.
	 *
	 * @return void
	 */
	public function test_frontend_data_contains_fallback_title() {
		update_option( 'edac_fix_iframe_missing_title', true );
		$this->fix->run();

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'iframe_missing_title', $data );
		$this->assertSame( 'Embedded content', $data['iframe_missing_title']['fallback_title'] );
	}
}
