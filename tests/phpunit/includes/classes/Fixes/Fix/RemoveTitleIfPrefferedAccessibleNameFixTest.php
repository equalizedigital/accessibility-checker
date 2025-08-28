<?php
/**
 * Test class for RemoveTitleIfPrefferedAccessibleNameFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\RemoveTitleIfPrefferedAccessibleNameFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the RemoveTitleIfPrefferedAccessibleNameFix class.
 */
class RemoveTitleIfPrefferedAccessibleNameFixTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		// Clean up any options that might interfere with tests.
		delete_option( 'edac_fix_remove_title_if_preferred_accessible_name' );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Clean up options after each test.
		delete_option( 'edac_fix_remove_title_if_preferred_accessible_name' );
		// Remove all filters to clean up.
		remove_all_filters( 'edac_filter_fixes_settings_fields' );
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		parent::tear_down();
	}

	/**
	 * Test that RemoveTitleIfPrefferedAccessibleNameFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'remove_title_if_preferred_accessible_name', RemoveTitleIfPrefferedAccessibleNameFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = RemoveTitleIfPrefferedAccessibleNameFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Prefer Accessible Label Attribute', $nicename );
	}

	/**
	 * Test get_fancyname returns translated string.
	 *
	 * @return void
	 */
	public function test_get_fancyname() {
		$fancyname = RemoveTitleIfPrefferedAccessibleNameFix::get_fancyname();
		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
		$this->assertEquals( 'Remove Unnecessary Title Attributes', $fancyname );
	}

	/**
	 * Test get_type returns frontend.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', RemoveTitleIfPrefferedAccessibleNameFix::get_type() );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
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
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_remove_title_if_preferred_accessible_name';
		$this->assertArrayHasKey( $expected_key, $fields );
		
		$field = $fields[ $expected_key ];
		$this->assertArrayHasKey( 'type', $field );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertArrayHasKey( 'labelledby', $field );
		$this->assertArrayHasKey( 'description', $field );
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'help_id', $field );
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Remove Title Attributes', $field['label'] );
		$this->assertEquals( 'accessible_name', $field['labelledby'] );
		$this->assertStringContainsString( 'Remove', $field['description'] );
		$this->assertStringContainsString( 'title', $field['description'] );
		$this->assertEquals( 'remove_title_if_preferred_accessible_name', $field['fix_slug'] );
		$this->assertEquals( 8494, $field['help_id'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new RemoveTitleIfPrefferedAccessibleNameFix();
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
		$this->assertArrayHasKey( 'edac_fix_remove_title_if_preferred_accessible_name', $fields );
	}

	/**
	 * Test field does not have upsell (not a Pro feature).
	 *
	 * @return void
	 */
	public function test_field_has_no_upsell() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		$this->assertArrayNotHasKey( 'upsell', $field );
	}

	/**
	 * Test run method does nothing when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_does_nothing_when_disabled() {
		delete_option( 'edac_fix_remove_title_if_preferred_accessible_name' );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should not add the frontend filter.
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method adds filter when option is enabled.
	 *
	 * @return void
	 */
	public function test_run_adds_filter_when_enabled() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', true );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should add the frontend filter.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test frontend data filter adds correct data when enabled.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter_adds_data() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', true );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		$this->assertArrayHasKey( 'remove_title_if_preferred_accessible_name', $data );
		$this->assertArrayHasKey( 'enabled', $data['remove_title_if_preferred_accessible_name'] );
		$this->assertTrue( $data['remove_title_if_preferred_accessible_name']['enabled'] );
	}

	/**
	 * Test frontend data filter preserves existing data.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter_preserves_existing_data() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', true );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fix->run();
		
		$existing_data = [
			'other_fix' => [
				'enabled' => true,
				'setting' => 'value',
			],
		];
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $existing_data );
		
		// Should preserve existing data.
		$this->assertArrayHasKey( 'other_fix', $data );
		$this->assertEquals( 'value', $data['other_fix']['setting'] );
		
		// Should add new data.
		$this->assertArrayHasKey( 'remove_title_if_preferred_accessible_name', $data );
	}

	/**
	 * Test that help_id is a positive integer.
	 *
	 * @return void
	 */
	public function test_help_id_is_positive_integer() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
		$this->assertEquals( 8494, $field['help_id'] );
	}

	/**
	 * Test that fix_slug matches the class slug.
	 *
	 * @return void
	 */
	public function test_fix_slug_matches_class_slug() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		$this->assertEquals( RemoveTitleIfPrefferedAccessibleNameFix::get_slug(), $field['fix_slug'] );
	}

	/**
	 * Test that field key follows expected pattern.
	 *
	 * @return void
	 */
	public function test_field_key_follows_pattern() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_' . RemoveTitleIfPrefferedAccessibleNameFix::get_slug();
		$this->assertArrayHasKey( $expected_key, $fields );
	}

	/**
	 * Test labelledby field is correctly set.
	 *
	 * @return void
	 */
	public function test_labelledby_field() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		$this->assertEquals( 'accessible_name', $field['labelledby'] );
	}

	/**
	 * Test field label is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_label_is_escaped() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		// The label should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['label'] );
		$this->assertEquals( 'Remove Title Attributes', $field['label'] );
	}

	/**
	 * Test field description contains code tag for title attribute.
	 *
	 * @return void
	 */
	public function test_field_description_contains_title_code() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		// The description should contain the title attribute in a code tag.
		$this->assertStringContainsString( '<code>title</code>', $field['description'] );
		$this->assertStringContainsString( 'accessible name', $field['description'] );
	}

	/**
	 * Test description is properly formatted with sprintf.
	 *
	 * @return void
	 */
	public function test_description_sprintf_formatting() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		// Test that sprintf formatting worked correctly.
		$this->assertStringContainsString( 'Remove <code>title</code> attributes from elements', $field['description'] );
		$this->assertStringContainsString( 'preferred accessible name', $field['description'] );
	}

	/**
	 * Test that the fix type is appropriate for frontend manipulation.
	 *
	 * @return void
	 */
	public function test_type_appropriate_for_frontend_fix() {
		$type = RemoveTitleIfPrefferedAccessibleNameFix::get_type();
		
		// 'frontend' type indicates this fix has frontend implementation.
		$this->assertEquals( 'frontend', $type );
	}

	/**
	 * Test that class is properly documented.
	 *
	 * @return void
	 */
	public function test_class_has_documentation() {
		$reflection  = new ReflectionClass( RemoveTitleIfPrefferedAccessibleNameFix::class );
		$doc_comment = $reflection->getDocComment();
		
		$this->assertIsString( $doc_comment );
		$this->assertStringContainsString( 'preferred accessible name', $doc_comment );
		$this->assertStringContainsString( '@since 1.16.0', $doc_comment );
	}

	/**
	 * Test run method with false option value.
	 *
	 * @return void
	 */
	public function test_run_with_false_option() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', false );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should not add the frontend filter.
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method with string option value.
	 *
	 * @return void
	 */
	public function test_run_with_string_option() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', '1' );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should add the frontend filter.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test frontend data contains hardcoded enabled value.
	 *
	 * @return void
	 */
	public function test_frontend_data_hardcoded_enabled() {
		update_option( 'edac_fix_remove_title_if_preferred_accessible_name', true );
		
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		// The enabled value is hardcoded to true in the filter.
		$this->assertTrue( $data['remove_title_if_preferred_accessible_name']['enabled'] );
	}

	/**
	 * Test that all required interface methods are implemented.
	 *
	 * @return void
	 */
	public function test_implements_all_interface_methods() {
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		$this->assertTrue( method_exists( $fix, 'register' ) );
		$this->assertTrue( method_exists( $fix, 'run' ) );
		$this->assertTrue( method_exists( RemoveTitleIfPrefferedAccessibleNameFix::class, 'get_slug' ) );
		$this->assertTrue( method_exists( RemoveTitleIfPrefferedAccessibleNameFix::class, 'get_nicename' ) );
		$this->assertTrue( method_exists( RemoveTitleIfPrefferedAccessibleNameFix::class, 'get_type' ) );
	}

	/**
	 * Test that both nicename and fancyname methods exist and return different values.
	 *
	 * @return void
	 */
	public function test_nicename_and_fancyname_different() {
		$nicename  = RemoveTitleIfPrefferedAccessibleNameFix::get_nicename();
		$fancyname = RemoveTitleIfPrefferedAccessibleNameFix::get_fancyname();
		
		$this->assertNotEquals( $nicename, $fancyname );
		$this->assertEquals( 'Prefer Accessible Label Attribute', $nicename );
		$this->assertEquals( 'Remove Unnecessary Title Attributes', $fancyname );
	}

	/**
	 * Test that class has comment explaining accessible name functionality.
	 *
	 * @return void
	 */
	public function test_class_comment_mentions_accessible_name() {
		$reflection  = new ReflectionClass( RemoveTitleIfPrefferedAccessibleNameFix::class );
		$doc_comment = $reflection->getDocComment();
		
		$this->assertStringContainsString( 'preferred accessible name', $doc_comment );
	}

	/**
	 * Test run method return type.
	 *
	 * @return void
	 */
	public function test_run_method_return_type() {
		$fix = new RemoveTitleIfPrefferedAccessibleNameFix();
		
		// Run method should not return anything.
		$this->assertNull( $fix->run() );
	}

	/**
	 * Test that the class name contains typo (documents current implementation).
	 *
	 * @return void
	 */
	public function test_class_name_contains_typo() {
		// This test documents that there's a typo in the class name: "Preffered" should be "Preferred".
		$class_name = RemoveTitleIfPrefferedAccessibleNameFix::class;
		$this->assertStringContainsString( 'Preffered', $class_name );
	}

	/**
	 * Test field structure is consistent with other free feature patterns.
	 *
	 * @return void
	 */
	public function test_free_feature_pattern_consistency() {
		$fix    = new RemoveTitleIfPrefferedAccessibleNameFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_remove_title_if_preferred_accessible_name'];
		
		// Check that this is a free feature (no upsell).
		$this->assertArrayNotHasKey( 'upsell', $field );
		
		// But should still have standard fields.
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'help_id', $field );
	}
}
