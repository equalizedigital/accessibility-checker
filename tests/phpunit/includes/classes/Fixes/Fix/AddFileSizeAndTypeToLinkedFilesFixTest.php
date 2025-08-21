<?php
/**
 * Test class for AddFileSizeAndTypeToLinkedFilesFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddFileSizeAndTypeToLinkedFilesFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the AddFileSizeAndTypeToLinkedFilesFix class.
 */
class AddFileSizeAndTypeToLinkedFilesFixTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		// Clean up any options that might interfere with tests.
		delete_option( 'edac_fix_add_file_size_and_type_to_linked_files' );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Clean up options after each test.
		delete_option( 'edac_fix_add_file_size_and_type_to_linked_files' );
		parent::tear_down();
	}

	/**
	 * Test that AddFileSizeAndTypeToLinkedFilesFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new AddFileSizeAndTypeToLinkedFilesFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'add_file_size_and_type_to_linked_files', AddFileSizeAndTypeToLinkedFilesFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = AddFileSizeAndTypeToLinkedFilesFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Add Size & Type To File Links', $nicename );
	}

	/**
	 * Test get_fancyname returns translated string.
	 *
	 * @return void
	 */
	public function test_get_fancyname() {
		$fancyname = AddFileSizeAndTypeToLinkedFilesFix::get_fancyname();
		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
		$this->assertEquals( 'Add Context to Linked Files', $fancyname );
	}

	/**
	 * Test get_type returns none.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'none', AddFileSizeAndTypeToLinkedFilesFix::get_type() );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new AddFileSizeAndTypeToLinkedFilesFix();
		
		// Remove any existing filters to start clean.
		remove_all_filters( 'edac_filter_fixes_settings_fields' );
		
		$fix->register();
		
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields' ) );
		$this->assertEquals( 10, has_filter( 'edac_filter_fixes_settings_fields', [ $fix, 'get_fields_array' ] ) );
	}

	/**
	 * Test get_fields_array returns correct field structure.
	 *
	 * @return void
	 */
	public function test_get_fields_array_structure() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_add_file_size_and_type_to_linked_files';
		$this->assertArrayHasKey( $expected_key, $fields );
		
		$field = $fields[ $expected_key ];
		$this->assertArrayHasKey( 'type', $field );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertArrayHasKey( 'labelledby', $field );
		$this->assertArrayHasKey( 'description', $field );
		$this->assertArrayHasKey( 'upsell', $field );
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'help_id', $field );
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Add File Size &amp; Type To Links', $field['label'] );
		$this->assertEquals( 'add_file_size_and_type_to_linked_files', $field['labelledby'] );
		$this->assertEquals( 'Add the file size and type to linked files that may trigger a download.', $field['description'] );
		$this->assertEquals( 'add_file_size_and_type_to_linked_files', $field['fix_slug'] );
		$this->assertEquals( 8492, $field['help_id'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new AddFileSizeAndTypeToLinkedFilesFix();
		$existing_fields = [
			'existing_field' => [
				'type'  => 'text',
				'label' => 'Existing Field',
			],
		];
		
		$fields = $fix->get_fields_array( $existing_fields );
		
		// Should preserve existing field.
		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertEquals( 'text', $fields['existing_field']['type'] );
		
		// Should add new field.
		$this->assertArrayHasKey( 'edac_fix_add_file_size_and_type_to_linked_files', $fields );
	}

	/**
	 * Test upsell is enabled by default (free version).
	 *
	 * @return void
	 */
	public function test_upsell_enabled_by_default() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		$this->assertTrue( $field['upsell'] );
	}

	/**
	 * Test upsell disabled when is_pro is set.
	 *
	 * @return void
	 */
	public function test_upsell_disabled_when_pro() {
		$fix = new AddFileSizeAndTypeToLinkedFilesFix();
		
		// Set is_pro property dynamically.
		$fix->is_pro = true;
		
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		$this->assertFalse( $field['upsell'] );
	}

	/**
	 * Test run method does nothing (intentionally empty).
	 *
	 * @return void
	 */
	public function test_run_method_is_empty() {
		$fix = new AddFileSizeAndTypeToLinkedFilesFix();
		
		// Should not throw any errors and should complete successfully.
		$this->assertNull( $fix->run() );
	}

	/**
	 * Test field label is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_label_is_escaped() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		
		// The label should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['label'] );
		$this->assertEquals( 'Add File Size &amp; Type To Links', $field['label'] );
	}

	/**
	 * Test field description is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_description_is_escaped() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		
		// The description should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['description'] );
		$this->assertEquals( 'Add the file size and type to linked files that may trigger a download.', $field['description'] );
	}

	/**
	 * Test that help_id is a positive integer.
	 *
	 * @return void
	 */
	public function test_help_id_is_positive_integer() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
		$this->assertEquals( 8492, $field['help_id'] );
	}

	/**
	 * Test that fix_slug matches the class slug.
	 *
	 * @return void
	 */
	public function test_fix_slug_matches_class_slug() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		
		$this->assertEquals( AddFileSizeAndTypeToLinkedFilesFix::get_slug(), $field['fix_slug'] );
	}

	/**
	 * Test that field key follows expected pattern.
	 *
	 * @return void
	 */
	public function test_field_key_follows_pattern() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_' . AddFileSizeAndTypeToLinkedFilesFix::get_slug();
		$this->assertArrayHasKey( $expected_key, $fields );
	}

	/**
	 * Test labelledby field is correctly set.
	 *
	 * @return void
	 */
	public function test_labelledby_field() {
		$fix    = new AddFileSizeAndTypeToLinkedFilesFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_add_file_size_and_type_to_linked_files'];
		
		$this->assertEquals( 'add_file_size_and_type_to_linked_files', $field['labelledby'] );
	}
}
