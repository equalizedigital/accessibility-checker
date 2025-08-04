<?php
/**
 * Test class for HTMLLangAndDirFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\HTMLLangAndDirFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the HTMLLangAndDirFix class.
 */
class HTMLLangAndDirFixTest extends WP_UnitTestCase {

	/**
	 * Test that HTMLLangAndDirFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new HTMLLangAndDirFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'lang_and_dir', HTMLLangAndDirFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = HTMLLangAndDirFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Add lang & dir Attributes', $nicename );
	}

	/**
	 * Test get_fancyname returns translated string.
	 *
	 * @return void
	 */
	public function test_get_fancyname() {
		$fancyname = HTMLLangAndDirFix::get_fancyname();
		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
		$this->assertEquals( 'Set Page Language', $fancyname );
	}

	/**
	 * Test get_type returns frontend.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', HTMLLangAndDirFix::get_type() );
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fix    = new HTMLLangAndDirFix();
		$fields = $fix->get_fields_array();

		$this->assertIsArray( $fields );
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
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new HTMLLangAndDirFix();
		$existing_fields = [ 'existing_field' => [ 'type' => 'text' ] ];
		$fields          = $fix->get_fields_array( $existing_fields );

		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertArrayHasKey( 'edac_fix_add_lang_and_dir', $fields );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new HTMLLangAndDirFix();

		$fix->register();

		// Verify that the filter was added.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_when_disabled() {
		$fix = new HTMLLangAndDirFix();

		// Ensure option is disabled.
		update_option( 'edac_fix_add_lang_and_dir', false );

		$fix->run();

		// Check that no filters were added.
		$this->assertFalse( has_filter( 'language_attributes', [ $fix, 'maybe_add_lang_and_dir' ] ) );
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method when option is enabled.
	 *
	 * @return void
	 */
	public function test_run_when_enabled() {
		$fix = new HTMLLangAndDirFix();

		// Enable the option.
		update_option( 'edac_fix_add_lang_and_dir', true );

		$fix->run();

		// Check that filters were added.
		$this->assertTrue( has_filter( 'language_attributes', [ $fix, 'maybe_add_lang_and_dir' ] ) !== false );
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) !== false );
	}

	/**
	 * Test the frontend data filter.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter() {
		$fix = new HTMLLangAndDirFix();

		// Enable the option.
		update_option( 'edac_fix_add_lang_and_dir', true );

		$fix->run();

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
		$fix = new HTMLLangAndDirFix();

		// Test with empty output (no existing attributes).
		$output = $fix->maybe_add_lang_and_dir( '' );

		$this->assertStringContainsString( 'lang=', $output );
		$this->assertStringContainsString( 'dir=', $output );
	}

	/**
	 * Test maybe_add_lang_and_dir method when lang attribute exists.
	 *
	 * @return void
	 */
	public function test_maybe_add_lang_and_dir_existing_lang() {
		$fix = new HTMLLangAndDirFix();

		// Test with existing lang attribute.
		$input  = 'lang="en-US"';
		$output = $fix->maybe_add_lang_and_dir( $input );

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
		$fix = new HTMLLangAndDirFix();

		// Test with existing dir attribute.
		$input  = 'dir="ltr"';
		$output = $fix->maybe_add_lang_and_dir( $input );

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
		$fix = new HTMLLangAndDirFix();

		// Test with both attributes existing.
		$input  = 'lang="en-US" dir="ltr"';
		$output = $fix->maybe_add_lang_and_dir( $input );

		// Should return unchanged.
		$this->assertEquals( $input, $output );
	}

	/**
	 * Test attribute values are properly escaped.
	 *
	 * @return void
	 */
	public function test_attribute_escaping() {
		$fix = new HTMLLangAndDirFix();

		$output = $fix->maybe_add_lang_and_dir( '' );

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
		$fix = new HTMLLangAndDirFix();

		$output = $fix->maybe_add_lang_and_dir( '' );

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
