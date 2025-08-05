<?php
/**
 * Test class for PreventLinksOpeningNewWindowFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\PreventLinksOpeningNewWindowFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the PreventLinksOpeningNewWindowFix class.
 */
class PreventLinksOpeningNewWindowFixTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		// Clean up any options that might interfere with tests.
		delete_option( 'edac_fix_prevent_links_opening_new_windows' );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Clean up options after each test.
		delete_option( 'edac_fix_prevent_links_opening_new_windows' );
		// Remove all filters to clean up.
		remove_all_filters( 'edac_filter_fixes_settings_fields' );
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		parent::tear_down();
	}

	/**
	 * Test that PreventLinksOpeningNewWindowFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new PreventLinksOpeningNewWindowFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'prevent_links_opening_new_windows', PreventLinksOpeningNewWindowFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = PreventLinksOpeningNewWindowFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Prevent Links Opening New Windows', $nicename );
	}

	/**
	 * Test get_type returns frontend.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', PreventLinksOpeningNewWindowFix::get_type() );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new PreventLinksOpeningNewWindowFix();
		
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
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_prevent_links_opening_new_windows';
		$this->assertArrayHasKey( $expected_key, $fields );
		
		$field = $fields[ $expected_key ];
		$this->assertArrayHasKey( 'type', $field );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertArrayHasKey( 'labelledby', $field );
		$this->assertArrayHasKey( 'description', $field );
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'group_name', $field );
		$this->assertArrayHasKey( 'help_id', $field );
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Block Links Opening New Windows', $field['label'] );
		$this->assertEquals( 'prevent_links_opening_in_new_windows', $field['labelledby'] );
		$this->assertStringContainsString( 'Prevent links from opening in a new window', $field['description'] );
		$this->assertEquals( 'prevent_links_opening_new_windows', $field['fix_slug'] );
		$this->assertEquals( 'Prevent Links Opening New Windows', $field['group_name'] );
		$this->assertEquals( 8493, $field['help_id'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new PreventLinksOpeningNewWindowFix();
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
		$this->assertArrayHasKey( 'edac_fix_prevent_links_opening_new_windows', $fields );
	}

	/**
	 * Test field does not have upsell (not a Pro feature).
	 *
	 * @return void
	 */
	public function test_field_has_no_upsell() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		$this->assertArrayNotHasKey( 'upsell', $field );
	}

	/**
	 * Test run method does nothing when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_does_nothing_when_disabled() {
		delete_option( 'edac_fix_prevent_links_opening_new_windows' );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		
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
		update_option( 'edac_fix_prevent_links_opening_new_windows', true );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		
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
		update_option( 'edac_fix_prevent_links_opening_new_windows', true );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		$fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		$this->assertArrayHasKey( 'prevent_links_opening_new_windows', $data );
		$this->assertArrayHasKey( 'enabled', $data['prevent_links_opening_new_windows'] );
		$this->assertTrue( $data['prevent_links_opening_new_windows']['enabled'] );
	}

	/**
	 * Test frontend data filter preserves existing data.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter_preserves_existing_data() {
		update_option( 'edac_fix_prevent_links_opening_new_windows', true );
		
		$fix = new PreventLinksOpeningNewWindowFix();
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
		$this->assertArrayHasKey( 'prevent_links_opening_new_windows', $data );
	}

	/**
	 * Test that help_id is a positive integer.
	 *
	 * @return void
	 */
	public function test_help_id_is_positive_integer() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
		$this->assertEquals( 8493, $field['help_id'] );
	}

	/**
	 * Test that fix_slug matches the class slug.
	 *
	 * @return void
	 */
	public function test_fix_slug_matches_class_slug() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		$this->assertEquals( PreventLinksOpeningNewWindowFix::get_slug(), $field['fix_slug'] );
	}

	/**
	 * Test that field key follows expected pattern.
	 *
	 * @return void
	 */
	public function test_field_key_follows_pattern() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_' . PreventLinksOpeningNewWindowFix::get_slug();
		$this->assertArrayHasKey( $expected_key, $fields );
	}

	/**
	 * Test labelledby field is correctly set.
	 *
	 * @return void
	 */
	public function test_labelledby_field() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		$this->assertEquals( 'prevent_links_opening_in_new_windows', $field['labelledby'] );
	}

	/**
	 * Test field label is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_label_is_escaped() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		// The label should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['label'] );
		$this->assertEquals( 'Block Links Opening New Windows', $field['label'] );
	}

	/**
	 * Test field description contains code tag for target attribute.
	 *
	 * @return void
	 */
	public function test_field_description_contains_target_code() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		// The description should contain the target attribute in a code tag.
		$this->assertStringContainsString( '<code>target="_blank"</code>', $field['description'] );
		$this->assertStringContainsString( 'removing', $field['description'] );
	}

	/**
	 * Test description is properly formatted with sprintf.
	 *
	 * @return void
	 */
	public function test_description_sprintf_formatting() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		// Test that sprintf formatting worked correctly.
		$this->assertStringContainsString( 'Prevent links from opening in a new window or tab by removing <code>target="_blank"</code>', $field['description'] );
	}

	/**
	 * Test that class has no fancyname method (not implemented).
	 *
	 * @return void
	 */
	public function test_no_fancyname_method() {
		$this->assertFalse( method_exists( PreventLinksOpeningNewWindowFix::class, 'get_fancyname' ) );
	}

	/**
	 * Test that the fix type is appropriate for frontend manipulation.
	 *
	 * @return void
	 */
	public function test_type_appropriate_for_frontend_fix() {
		$type = PreventLinksOpeningNewWindowFix::get_type();
		
		// 'frontend' type indicates this fix has frontend implementation.
		$this->assertEquals( 'frontend', $type );
	}

	/**
	 * Test that class is properly documented.
	 *
	 * @return void
	 */
	public function test_class_has_documentation() {
		$reflection  = new ReflectionClass( PreventLinksOpeningNewWindowFix::class );
		$doc_comment = $reflection->getDocComment();
		
		$this->assertIsString( $doc_comment );
		$this->assertStringContainsString( 'Prevents links from opening in new windows', $doc_comment );
		$this->assertStringContainsString( '@since 1.16.0', $doc_comment );
	}

	/**
	 * Test run method with false option value.
	 *
	 * @return void
	 */
	public function test_run_with_false_option() {
		update_option( 'edac_fix_prevent_links_opening_new_windows', false );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		
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
		update_option( 'edac_fix_prevent_links_opening_new_windows', '1' );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should add the frontend filter.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test group_name field is set correctly.
	 *
	 * @return void
	 */
	public function test_group_name_field() {
		$fix    = new PreventLinksOpeningNewWindowFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_prevent_links_opening_new_windows'];
		
		$this->assertEquals( PreventLinksOpeningNewWindowFix::get_nicename(), $field['group_name'] );
	}

	/**
	 * Test frontend data contains hardcoded enabled value.
	 *
	 * @return void
	 */
	public function test_frontend_data_hardcoded_enabled() {
		update_option( 'edac_fix_prevent_links_opening_new_windows', true );
		
		$fix = new PreventLinksOpeningNewWindowFix();
		$fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		// The enabled value is hardcoded to true in the filter.
		$this->assertTrue( $data['prevent_links_opening_new_windows']['enabled'] );
	}

	/**
	 * Test that all required interface methods are implemented.
	 *
	 * @return void
	 */
	public function test_implements_all_interface_methods() {
		$fix = new PreventLinksOpeningNewWindowFix();
		
		$this->assertTrue( method_exists( $fix, 'register' ) );
		$this->assertTrue( method_exists( $fix, 'run' ) );
		$this->assertTrue( method_exists( PreventLinksOpeningNewWindowFix::class, 'get_slug' ) );
		$this->assertTrue( method_exists( PreventLinksOpeningNewWindowFix::class, 'get_nicename' ) );
		$this->assertTrue( method_exists( PreventLinksOpeningNewWindowFix::class, 'get_type' ) );
	}
}
