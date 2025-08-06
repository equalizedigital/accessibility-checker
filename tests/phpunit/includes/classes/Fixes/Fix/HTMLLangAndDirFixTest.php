<?php
/**
 * Test class for HTMLLangAndDirFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\HTMLLangAndDirFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

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
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();

		$this->assertArrayHasKey( 'edac_fix_add_lang_and_dir', $fields );

		$field = $fields['edac_fix_add_lang_and_dir'];
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Add &quot;lang&quot; &amp; &quot;dir&quot; Attributes', $field['label'] );
		$this->assertEquals( 'add_lang_and_dir', $field['labelledby'] );
		$this->assertEquals( 'lang_and_dir', $field['fix_slug'] );
		$this->assertEquals( 'Add lang & dir Attributes', $field['group_name'] );
		$this->assertEquals( 8487, $field['help_id'] );
		$this->assertStringContainsString( 'lang', $field['description'] );
		$this->assertStringContainsString( 'dir', $field['description'] );
		$this->assertStringContainsString( '&lt;html&gt;', $field['description'] );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$this->fix->register();

		// Verify that the filter was added.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_when_disabled() {
		// Ensure option is disabled.
		update_option( 'edac_fix_add_lang_and_dir', false );

		$this->fix->run();

		// Check that no filters were added.
		$this->assertFalse( has_filter( 'language_attributes', [ $this->fix, 'maybe_add_lang_and_dir' ] ) );
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method when option is enabled.
	 *
	 * @return void
	 */
	public function test_run_when_enabled() {
		// Enable the option.
		update_option( 'edac_fix_add_lang_and_dir', true );

		$this->fix->run();

		// Check that filters were added.
		$this->assertTrue( has_filter( 'language_attributes', [ $this->fix, 'maybe_add_lang_and_dir' ] ) !== false );
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) !== false );
	}

	/**
	 * Test the frontend data filter.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter() {
		// Enable the option.
		update_option( 'edac_fix_add_lang_and_dir', true );

		$this->fix->run();

		// Test the filter output.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );

		$this->assertArrayHasKey( 'lang_and_dir', $data );
		$this->assertArrayHasKey( 'enabled', $data['lang_and_dir'] );
		$this->assertArrayHasKey( 'lang', $data['lang_and_dir'] );
		$this->assertArrayHasKey( 'dir', $data['lang_and_dir'] );
		$this->assertTrue( $data['lang_and_dir']['enabled'] );
		$this->assertIsString( $data['lang_and_dir']['lang'] );
		$this->assertIsString( $data['lang_and_dir']['dir'] );
	}

	/**
	 * Test maybe_add_lang_and_dir method when attributes are missing.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_missing_attributes() {
		// Test with empty output (no existing attributes).
		$output = $this->fix->maybe_add_lang_and_dir( '' );

		$this->assertStringContainsString( 'lang=', $output );
		$this->assertStringContainsString( 'dir=', $output );
	}

	/**
	 * Test maybe_add_lang_and_dir method when lang attribute exists.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_existing_lang() {
		// Test with existing lang attribute.
		$input  = 'lang="en-US"';
		$output = $this->fix->maybe_add_lang_and_dir( $input );

		$this->assertStringContainsString( 'lang="en-US"', $output );
		$this->assertStringContainsString( 'dir=', $output );
		// Should not add another lang attribute.
		$this->assertEquals( 1, substr_count( $output, 'lang=' ) );
	}

	/**
	 * Test maybe_add_lang_and_dir method when dir attribute exists.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_existing_dir() {
		// Test with existing dir attribute.
		$input  = 'dir="ltr"';
		$output = $this->fix->maybe_add_lang_and_dir( $input );

		$this->assertStringContainsString( 'dir="ltr"', $output );
		$this->assertStringContainsString( 'lang=', $output );
		// Should not add another dir attribute.
		$this->assertEquals( 1, substr_count( $output, 'dir=' ) );
	}

	/**
	 * Test maybe_add_lang_and_dir method when both attributes exist.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_both_exist() {
		// Test with both attributes existing.
		$input  = 'lang="en-US" dir="ltr"';
		$output = $this->fix->maybe_add_lang_and_dir( $input );

		// Should return unchanged.
		$this->assertEquals( $input, $output );
	}

	/**
	 * Test attribute values are properly escaped.
	 *
	 * @return void
	 */
	public function test_attribute_escaping() {
		$output = $this->fix->maybe_add_lang_and_dir( '' );

		// Check that quotes are properly escaped in attributes.
		$this->assertStringContainsString( 'lang="', $output );
		$this->assertStringContainsString( 'dir="', $output );
		$this->assertStringNotContainsString( 'lang=\\"', $output );
		$this->assertStringNotContainsString( 'dir=\\"', $output );
	}

	/**
	 * Test direction detection.
	 *
	 * @return void
	 */
	public function test_direction_detection() {
		$output = $this->fix->maybe_add_lang_and_dir( '' );

		// Since we're running in a default WordPress installation, dir should be 'ltr'.
		$this->assertStringContainsString( 'dir="ltr"', $output );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_add_lang_and_dir' );
		parent::tearDown();
	}
}
