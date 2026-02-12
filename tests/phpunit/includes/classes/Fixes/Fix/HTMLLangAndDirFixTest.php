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

	/**
	 * Test that get_fancyname returns expected string.
	 *
	 * @return void
	 */
	public function test_get_fancyname_returns_expected_string() {
		$fancyname = HTMLLangAndDirFix::get_fancyname();

		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
		$this->assertStringContainsString( 'Page Language', $fancyname );
	}

	/**
	 * Test that maybe_add_lang_and_dir adds both attributes when missing.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_adds_both_when_missing() {
		$result = $this->fix->maybe_add_lang_and_dir( '' );

		$this->assertStringContainsString( 'lang="', $result );
		$this->assertStringContainsString( 'dir="', $result );
	}

	/**
	 * Test that maybe_add_lang_and_dir skips lang when already present.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_skips_lang_when_already_present() {
		$result = $this->fix->maybe_add_lang_and_dir( 'lang="en-US"' );

		$this->assertEquals( 1, substr_count( $result, 'lang=' ) );
		$this->assertStringContainsString( 'dir="', $result );
	}

	/**
	 * Test that maybe_add_lang_and_dir skips dir when already present.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_skips_dir_when_already_present() {
		$result = $this->fix->maybe_add_lang_and_dir( 'dir="ltr"' );

		$this->assertStringContainsString( 'lang="', $result );
		$this->assertEquals( 1, substr_count( $result, 'dir=' ) );
	}

	/**
	 * Test that maybe_add_lang_and_dir leaves output unchanged when both present.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_unchanged_when_both_present() {
		$input  = 'lang="en-US" dir="ltr"';
		$result = $this->fix->maybe_add_lang_and_dir( $input );

		$this->assertEquals( $input, $result );
	}
}
