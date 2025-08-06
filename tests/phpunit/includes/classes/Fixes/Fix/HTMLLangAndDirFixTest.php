<?php
/**
 * Test class for HTMLLangAndDirFix.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\HTMLLangAndDirFix;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the HTMLLangAndDirFix class.
 */
class HTMLLangAndDirFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new HTMLLangAndDirFix();
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
		return 'lang_and_dir';
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
		return HTMLLangAndDirFix::class;
	}

	/**
	 * Get the fix option names.
	 *
	 * @return array
	 */
	protected function get_fix_option_names(): array {
		return [ 'edac_fix_add_lang_and_dir' ];
	}

	/**
	 * Test HTML language and direction modification.
	 *
	 * @return void
	 */
	public function test_html_lang_dir_modification() {
		update_option( 'edac_fix_add_lang_and_dir', true );
		$this->fix->run();
		
		// Test frontend data filter.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'lang_and_dir', $data );
		$this->assertTrue( $data['lang_and_dir']['enabled'] );
	}
}
