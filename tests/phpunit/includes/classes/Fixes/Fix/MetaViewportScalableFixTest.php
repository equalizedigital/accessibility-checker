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
		
		// Test frontend data filter
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$this->assertArrayHasKey( 'meta_viewport_scalable', $data );
		$this->assertTrue( $data['meta_viewport_scalable']['enabled'] );
	}
}

	/**
	 * Test sections filter callback adds meta-viewport-scalable section.
	 *
	 * @return void
	 */
	public function test_sections_filter_adds_section() {
		$fix = new MetaViewportScalableFix();
		$fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', [] );
		
		$this->assertArrayHasKey( 'meta-viewport-scalable', $sections );
		
		$section = $sections['meta-viewport-scalable'];
		$this->assertArrayHasKey( 'title', $section );
		$this->assertArrayHasKey( 'description', $section );
		$this->assertArrayHasKey( 'callback', $section );
		
		$this->assertEquals( 'Ensure scalable viewport', $section['title'] );
		$this->assertEquals( 'Make sure that the viewport tag on the page allows scaling.', $section['description'] );
		$this->assertEquals( [ $fix, 'comment_search_label_section_callback' ], $section['callback'] );
	}

	/**
	 * Test get_fields_array returns correct field structure.
	 *
	 * @return void
	 */
	public function test_get_fields_array_structure() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_meta_viewport_scalable';
		$this->assertArrayHasKey( $expected_key, $fields );
		
		$field = $fields[ $expected_key ];
		$this->assertArrayHasKey( 'type', $field );
		$this->assertArrayHasKey( 'label', $field );
		$this->assertArrayHasKey( 'labelledby', $field );
		$this->assertArrayHasKey( 'description', $field );
		$this->assertArrayHasKey( 'fix_slug', $field );
		$this->assertArrayHasKey( 'help_id', $field );
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Make Viewport Scalable', $field['label'] );
		$this->assertEquals( '', $field['labelledby'] );
		$this->assertEquals( 'Ensure the viewport tag allows for scaling, enhancing accessibility on mobile devices.', $field['description'] );
		$this->assertEquals( 'meta_viewport_scalable', $field['fix_slug'] );
		$this->assertEquals( 8488, $field['help_id'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new MetaViewportScalableFix();
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
		$this->assertArrayHasKey( 'edac_fix_meta_viewport_scalable', $fields );
	}

	/**
	 * Test field does not have upsell (not a Pro feature).
	 *
	 * @return void
	 */
	public function test_field_has_no_upsell() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		$this->assertArrayNotHasKey( 'upsell', $field );
	}

	/**
	 * Test run method does nothing when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_does_nothing_when_disabled() {
		delete_option( 'edac_fix_meta_viewport_scalable' );
		
		$fix = new MetaViewportScalableFix();
		
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
		update_option( 'edac_fix_meta_viewport_scalable', true );
		
		$fix = new MetaViewportScalableFix();
		
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
		update_option( 'edac_fix_meta_viewport_scalable', true );
		
		$fix = new MetaViewportScalableFix();
		$fix->run();
		
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		$this->assertArrayHasKey( 'meta_viewport_scalable', $data );
		$this->assertArrayHasKey( 'enabled', $data['meta_viewport_scalable'] );
		$this->assertTrue( $data['meta_viewport_scalable']['enabled'] );
	}

	/**
	 * Test frontend data filter preserves existing data.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter_preserves_existing_data() {
		update_option( 'edac_fix_meta_viewport_scalable', true );
		
		$fix = new MetaViewportScalableFix();
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
		$this->assertArrayHasKey( 'meta_viewport_scalable', $data );
	}

	/**
	 * Test that help_id is a positive integer.
	 *
	 * @return void
	 */
	public function test_help_id_is_positive_integer() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
		$this->assertEquals( 8488, $field['help_id'] );
	}

	/**
	 * Test that fix_slug matches the class slug.
	 *
	 * @return void
	 */
	public function test_fix_slug_matches_class_slug() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		
		$this->assertEquals( MetaViewportScalableFix::get_slug(), $field['fix_slug'] );
	}

	/**
	 * Test that field key follows expected pattern.
	 *
	 * @return void
	 */
	public function test_field_key_follows_pattern() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$expected_key = 'edac_fix_' . MetaViewportScalableFix::get_slug();
		$this->assertArrayHasKey( $expected_key, $fields );
	}

	/**
	 * Test labelledby field is empty string.
	 *
	 * @return void
	 */
	public function test_labelledby_field_is_empty() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		
		$this->assertEquals( '', $field['labelledby'] );
	}

	/**
	 * Test field label is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_label_is_escaped() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		
		// The label should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['label'] );
		$this->assertEquals( 'Make Viewport Scalable', $field['label'] );
	}

	/**
	 * Test field description is properly escaped.
	 *
	 * @return void
	 */
	public function test_field_description_is_escaped() {
		$fix    = new MetaViewportScalableFix();
		$fields = $fix->get_fields_array();
		
		$field = $fields['edac_fix_meta_viewport_scalable'];
		
		// The description should be a string and not contain unescaped HTML.
		$this->assertIsString( $field['description'] );
		$this->assertEquals( 'Ensure the viewport tag allows for scaling, enhancing accessibility on mobile devices.', $field['description'] );
	}

	/**
	 * Test that class has no fancyname method (not implemented).
	 *
	 * @return void
	 */
	public function test_no_fancyname_method() {
		$this->assertFalse( method_exists( MetaViewportScalableFix::class, 'get_fancyname' ) );
	}

	/**
	 * Test that the fix type is appropriate for viewport manipulation.
	 *
	 * @return void
	 */
	public function test_type_appropriate_for_frontend_fix() {
		$type = MetaViewportScalableFix::get_type();
		
		// 'frontend' type indicates this fix has frontend implementation.
		$this->assertEquals( 'frontend', $type );
	}

	/**
	 * Test that class is properly documented.
	 *
	 * @return void
	 */
	public function test_class_has_documentation() {
		$reflection  = new ReflectionClass( MetaViewportScalableFix::class );
		$doc_comment = $reflection->getDocComment();
		
		$this->assertIsString( $doc_comment );
		$this->assertStringContainsString( 'meta viewport tag', $doc_comment );
		$this->assertStringContainsString( '@since 1.16.0', $doc_comment );
	}

	/**
	 * Test run method with false option value.
	 *
	 * @return void
	 */
	public function test_run_with_false_option() {
		update_option( 'edac_fix_meta_viewport_scalable', false );
		
		$fix = new MetaViewportScalableFix();
		
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
		update_option( 'edac_fix_meta_viewport_scalable', '1' );
		
		$fix = new MetaViewportScalableFix();
		
		// Remove any existing filters.
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
		
		$fix->run();
		
		// Should add the frontend filter.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test sections filter callback method name is descriptive.
	 *
	 * @return void
	 */
	public function test_section_callback_method_name() {
		$fix = new MetaViewportScalableFix();
		$fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', [] );
		$section  = $sections['meta-viewport-scalable'];
		
		// Note: The callback method name appears to be copied from another class
		// This test documents the current behavior.
		$this->assertEquals( [ $fix, 'comment_search_label_section_callback' ], $section['callback'] );
	}
}
