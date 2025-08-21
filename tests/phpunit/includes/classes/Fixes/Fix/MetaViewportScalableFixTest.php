<?php
/**
 * Test class for MetaViewportScalableFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\MetaViewportScalableFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the MetaViewportScalableFix class.
 */
class MetaViewportScalableFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new MetaViewportScalableFix();
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
		return 'meta_viewport_scalable';
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
		return MetaViewportScalableFix::class;
	}

	/**
	 * Test that register adds settings sections.
	 *
	 * @return void
	 */
	public function test_register_adds_settings_sections() {
		$this->fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', [] );
		$this->assertArrayHasKey( 'meta-viewport-scalable', $sections );
	}

	/**
	 * Test viewport manipulation functionality.
	 *
	 * @return void
	 */
	public function test_viewport_tag_modification() {
		update_option( 'edac_fix_meta_viewport_scalable', true );
		$this->fix->run();
		
		// Test frontend data filter.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'meta_viewport_scalable', $data );
		$this->assertTrue( $data['meta_viewport_scalable']['enabled'] );
	}
}
