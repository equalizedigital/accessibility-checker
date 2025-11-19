<?php
/**
 * Test class for BlockPDFUploadsFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\BlockPDFUploadsFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the BlockPDFUploadsFix class.
 */
class BlockPDFUploadsFixTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		// Clean up any options that might interfere with tests.
		delete_option( 'edac_fix_block_pdf_uploads' );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Clean up options after each test.
		delete_option( 'edac_fix_block_pdf_uploads' );
		parent::tear_down();
	}

	/**
	 * Test that BlockPDFUploadsFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new BlockPDFUploadsFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'block_pdf_uploads', BlockPDFUploadsFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = BlockPDFUploadsFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Block PDF Uploads', $nicename );
	}

	/**
	 * Test get_type returns none.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'none', BlockPDFUploadsFix::get_type() );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new BlockPDFUploadsFix();
		
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
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_block_pdf_uploads';
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
		$this->assertEquals( 'Block PDF Uploads', $field['label'] );
		$this->assertEquals( 'block_pdf_uploads', $field['labelledby'] );
		$this->assertStringContainsString( 'Restrict PDF uploads for users without the', $field['description'] );
		$this->assertEquals( 'block_pdf_uploads', $field['fix_slug'] );
		$this->assertEquals( 8486, $field['help_id'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new BlockPDFUploadsFix();
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
		$this->assertArrayHasKey( 'edac_fix_block_pdf_uploads', $fields );
	}

	/**
	 * Test upsell is enabled by default (free version).
	 *
	 * @return void
	 */
	public function test_upsell_enabled_by_default() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		$this->assertTrue( $field['upsell'] );
	}

	/**
	 * Test upsell disabled when is_pro is set.
	 *
	 * @return void
	 */
	public function test_upsell_disabled_when_pro() {
		$fix = new BlockPDFUploadsFix();
		
		// Dynamically add the is_pro property for testing.
		$fix->is_pro = true;
		
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		$this->assertFalse( $field['upsell'] );
	}

	/**
	 * Test run method does nothing (intentionally empty).
	 *
	 * @return void
	 */
	public function test_run_method_is_empty() {
		$fix = new BlockPDFUploadsFix();
		
		// Should not throw any errors and should complete successfully.
		$this->assertNull( $fix->run() );
	}

	/**
	 * Test field label is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_label_is_escaped() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		// The label should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['label'] );
		$this->assertEquals( 'Block PDF Uploads', $field['label'] );
	}

	/**
	 * Test field description contains code tag for capability.
	 *
	 * @return void
	 */
	public function test_field_description_contains_capability_code() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		// The description should contain the capability in a code tag.
		$this->assertStringContainsString( '<code>edac_upload_pdf</code>', $field['description'] );
		$this->assertStringContainsString( 'allowed for admins by default', $field['description'] );
	}

	/**
	 * Test that help_id is a positive integer.
	 *
	 * @return void
	 */
	public function test_help_id_is_positive_integer() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
		$this->assertEquals( 8486, $field['help_id'] );
	}

	/**
	 * Test that fix_slug matches the class slug.
	 *
	 * @return void
	 */
	public function test_fix_slug_matches_class_slug() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		$this->assertEquals( BlockPDFUploadsFix::get_slug(), $field['fix_slug'] );
	}

	/**
	 * Test that field key follows expected pattern.
	 *
	 * @return void
	 */
	public function test_field_key_follows_pattern() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_block_pdf_uploads';
		$this->assertArrayHasKey( $expected_key, $fields );
	}

	/**
	 * Test labelledby field is correctly set.
	 *
	 * @return void
	 */
	public function test_labelledby_field() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		$this->assertEquals( 'block_pdf_uploads', $field['labelledby'] );
	}

	/**
	 * Test description is properly formatted with sprintf.
	 *
	 * @return void
	 */
	public function test_description_sprintf_formatting() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		// Test that sprintf formatting worked correctly.
		$this->assertStringContainsString( 'Restrict PDF uploads for users without the <code>edac_upload_pdf</code> capability', $field['description'] );
	}

	/**
	 * Test that class has no fancyname method (not implemented).
	 *
	 * @return void
	 */
	public function test_no_fancyname_method() {
		$this->assertFalse( method_exists( BlockPDFUploadsFix::class, 'get_fancyname' ) );
	}

	/**
	 * Test that the fix type is appropriate for an upload restriction.
	 *
	 * @return void
	 */
	public function test_type_appropriate_for_upload_restriction() {
		$type = BlockPDFUploadsFix::get_type();
		
		// 'none' type indicates this fix doesn't have frontend implementation.
		$this->assertEquals( 'none', $type );
	}

	/**
	 * Test that class is properly documented.
	 *
	 * @return void
	 */
	public function test_class_has_documentation() {
		$reflection  = new ReflectionClass( BlockPDFUploadsFix::class );
		$doc_comment = $reflection->getDocComment();
		
		$this->assertIsString( $doc_comment );
		$this->assertStringContainsString( 'Fix for blocking PDF uploads', $doc_comment );
		$this->assertStringContainsString( '@since 1.16.0', $doc_comment );
	}

	/**
	 * Test field structure is consistent with other Pro feature patterns.
	 *
	 * @return void
	 */
	public function test_pro_feature_pattern_consistency() {
		$fix    = new BlockPDFUploadsFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_block_pdf_uploads'];
		
		// Check that all required Pro feature fields are present.
		$this->assertArrayHasKey( 'upsell', $field );
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'help_id', $field );
		
		// Upsell should be true by default (free version).
		$this->assertTrue( $field['upsell'] );
	}
}
