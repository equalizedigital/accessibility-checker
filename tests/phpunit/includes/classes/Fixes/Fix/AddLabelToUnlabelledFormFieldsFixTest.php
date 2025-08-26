<?php
/**
 * Tests for AddLabelToUnlabelledFormFieldsFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddLabelToUnlabelledFormFieldsFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
use WP_UnitTestCase;

/**
 * AddLabelToUnlabelledFormFieldsFix test case
 */
class AddLabelToUnlabelledFormFieldsFixTest extends WP_UnitTestCase {

	/**
	 * Test fix instance
	 *
	 * @var AddLabelToUnlabelledFormFieldsFix
	 */
	private $fix;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new AddLabelToUnlabelledFormFieldsFix();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_add_label_to_unlabelled_form_fields' );

		parent::tearDown();
	}

	/**
	 * Test that the fix implements FixInterface
	 */
	public function test_implements_fix_interface() {
		$this->assertInstanceOf( FixInterface::class, $this->fix );
	}

	/**
	 * Test get_slug method
	 */
	public function test_get_slug() {
		$this->assertEquals( 'add_label_to_unlabelled_form_fields', AddLabelToUnlabelledFormFieldsFix::get_slug() );
	}

	/**
	 * Test get_nicename method
	 */
	public function test_get_nicename() {
		$this->assertEquals( 'Add Labels to Unlabelled Form Fields', AddLabelToUnlabelledFormFieldsFix::get_nicename() );
	}

	/**
	 * Test get_fancyname method
	 */
	public function test_get_fancyname() {
		$this->assertEquals( 'Label Form Fields', AddLabelToUnlabelledFormFieldsFix::get_fancyname() );
	}

	/**
	 * Test get_type method
	 */
	public function test_get_type() {
		$this->assertEquals( 'none', AddLabelToUnlabelledFormFieldsFix::get_type() );
	}

	/**
	 * Test register method adds settings sections
	 */
	public function test_register_adds_settings_sections() {
		$sections = [];
		$this->fix->register();

		$sections = apply_filters( 'edac_filter_fixes_settings_sections', $sections );

		$this->assertArrayHasKey( 'add_label_to_unlabelled_form_fields', $sections );
		$this->assertEquals( 'Label Form Fields', $sections['add_label_to_unlabelled_form_fields']['title'] );
		$this->assertEquals( [ $this->fix, 'add_label_to_unlabelled_form_fields_section_callback' ], $sections['add_label_to_unlabelled_form_fields']['callback'] );
	}

	/**
	 * Test register method adds settings fields filter
	 */
	public function test_register_adds_settings_fields_filter() {
		$this->fix->register();

		$this->assertNotFalse( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) );
	}

	/**
	 * Test get_fields_array method returns correct field for free version
	 */
	public function test_get_fields_array_free_version() {
		$fields = $this->fix->get_fields_array();

		$this->assertArrayHasKey( 'edac_fix_add_label_to_unlabelled_form_fields', $fields );
		$field = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		$this->assertEquals( 'Label Form Fields', $field['label'] );
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'add_label_to_unlabelled_form_fields', $field['labelledby'] );
		$this->assertEquals( 'Add labels to unlabelled form fields if field purpose can be determined.', $field['description'] );
		$this->assertEquals( 'add_label_to_unlabelled_form_fields', $field['section'] );
		$this->assertTrue( $field['upsell'] ); // Should be true for free version.
		$this->assertEquals( 'Add Labels to Unlabelled Form Fields', $field['group_name'] );
		$this->assertEquals( 'Label Form Fields', $field['fancy_name'] );
		$this->assertEquals( 'add_label_to_unlabelled_form_fields', $field['fix_slug'] );
		$this->assertEquals( 8497, $field['help_id'] );
	}

	/**
	 * Test get_fields_array method for pro version
	 */
	public function test_get_fields_array_pro_version() {
		// Create anonymous class extending the fix to simulate pro version.
		$pro_fix = new class() extends AddLabelToUnlabelledFormFieldsFix {
			/**
			 * Pro version flag.
			 *
			 * @var bool
			 */
			public $is_pro = true;
		};

		$fields = $pro_fix->get_fields_array();

		$this->assertArrayHasKey( 'edac_fix_add_label_to_unlabelled_form_fields', $fields );
		$field = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		$this->assertFalse( $field['upsell'] ); // Should be false for pro version.
	}

	/**
	 * Test get_fields_array preserves existing fields
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$existing_fields = [
			'existing_field' => [
				'type'  => 'text',
				'label' => 'Existing Field',
			],
		];

		$fields = $this->fix->get_fields_array( $existing_fields );

		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertArrayHasKey( 'edac_fix_add_label_to_unlabelled_form_fields', $fields );
		$this->assertEquals( 'text', $fields['existing_field']['type'] );
		$this->assertEquals( 'Existing Field', $fields['existing_field']['label'] );
	}

	/**
	 * Test run method does nothing (intentionally empty)
	 */
	public function test_run_does_nothing() {
		// The run method is intentionally left empty for this fix.
		$this->assertNull( $this->fix->run() );
	}

	/**
	 * Test add_label_to_unlabelled_form_fields_section_callback method
	 */
	public function test_section_callback_outputs_description() {
		ob_start();
		$this->fix->add_label_to_unlabelled_form_fields_section_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<p>', $output );
		$this->assertStringContainsString( 'Attempt to add labels to form fields that are missing them.', $output );
		$this->assertStringContainsString( '<code>.edac-generated-label</code>', $output );
		$this->assertStringContainsString( 'Note: You may need to add custom styles', $output );
		$this->assertStringContainsString( '</p>', $output );
	}

	/**
	 * Test field structure for upsell functionality
	 */
	public function test_field_structure_upsell_functionality() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		// Test required field properties for upsell.
		$this->assertArrayHasKey( 'upsell', $field );
		$this->assertArrayHasKey( 'group_name', $field );
		$this->assertArrayHasKey( 'fancy_name', $field );

		// Test that upsell is boolean.
		$this->assertIsBool( $field['upsell'] );

		// Test that group_name and fancy_name are strings.
		$this->assertIsString( $field['group_name'] );
		$this->assertIsString( $field['fancy_name'] );
	}

	/**
	 * Test pro property handling
	 */
	public function test_pro_property_handling() {
		// Test that the property exists and defaults to false (free version).
		$this->assertTrue( isset( $this->fix->is_pro ) );
		$this->assertFalse( $this->fix->is_pro );

		// Test that upsell is true when is_pro property is false.
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_label_to_unlabelled_form_fields'];
		$this->assertTrue( $field['upsell'] );
	}

	/**
	 * Test dynamic property assignment for pro version
	 */
	public function test_dynamic_property_assignment_pro_version() {
		// Dynamically set is_pro property.
		$this->fix->is_pro = true;

		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		$this->assertFalse( $field['upsell'] );
	}

	/**
	 * Test dynamic property assignment for false pro value
	 */
	public function test_dynamic_property_assignment_false_pro_value() {
		// Set is_pro property to false explicitly.
		$this->fix->is_pro = false;

		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		$this->assertTrue( $field['upsell'] );
	}

	/**
	 * Test field structure matches expected pattern
	 */
	public function test_field_structure_matches_pattern() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_add_label_to_unlabelled_form_fields'];

		// Test required field properties.
		$required_properties = [
			'label',
			'type',
			'labelledby',
			'description',
			'section',
			'upsell',
			'group_name',
			'fancy_name',
			'fix_slug',
			'help_id',
		];

		foreach ( $required_properties as $property ) {
			$this->assertArrayHasKey( $property, $field, "Field missing required property: {$property}" );
		}

		// Test field values are appropriate types.
		$this->assertIsString( $field['label'] );
		$this->assertNotEmpty( $field['label'] );
		$this->assertIsString( $field['type'] );
		$this->assertNotEmpty( $field['type'] );
		$this->assertIsBool( $field['upsell'] );
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
	}

	/**
	 * Test section callback includes proper CSS class reference
	 */
	public function test_section_callback_includes_css_class_reference() {
		ob_start();
		$this->fix->add_label_to_unlabelled_form_fields_section_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( '.edac-generated-label', $output );
		$this->assertStringContainsString( '<code>', $output );
		$this->assertStringContainsString( '</code>', $output );
	}

	/**
	 * Test that class has proper inheritance structure
	 */
	public function test_class_inheritance_structure() {
		// Verify the class implements the correct interface.
		$interfaces = class_implements( $this->fix );
		$this->assertContains( FixInterface::class, $interfaces );

		// Verify the class has required methods from interface.
		$this->assertTrue( method_exists( $this->fix, 'get_slug' ) );
		$this->assertTrue( method_exists( $this->fix, 'get_nicename' ) );
		$this->assertTrue( method_exists( $this->fix, 'get_fancyname' ) );
		$this->assertTrue( method_exists( $this->fix, 'get_type' ) );
		$this->assertTrue( method_exists( $this->fix, 'register' ) );
		$this->assertTrue( method_exists( $this->fix, 'run' ) );
	}

	/**
	 * Test get_type returns 'none' indicating no frontend implementation
	 */
	public function test_get_type_indicates_no_frontend_implementation() {
		$this->assertEquals( 'none', $this->fix->get_type() );
	}

	/**
	 * Test that run method is intentionally empty
	 */
	public function test_run_method_intentionally_empty() {
		// Use reflection to check if run method body is empty.
		$reflection = new \ReflectionMethod( $this->fix, 'run' );
		$start_line = $reflection->getStartLine();
		$end_line   = $reflection->getEndLine();
		$length     = $end_line - $start_line;

		// The method should be very short (just opening/closing braces and comment).
		$this->assertLessThan( 5, $length );
	}

	/**
	 * Test integration with WordPress hooks system
	 */
	public function test_integration_with_wordpress_hooks() {
		// Test that register method properly sets up hooks.
		$this->fix->register();

		// Verify section filter is registered.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_sections' ) );

		// Verify fields filter is registered.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields' ) );
	}

	/**
	 * Test section dynamic method name construction
	 */
	public function test_section_dynamic_method_name_construction() {
		$sections = [];
		$this->fix->register();

		$sections = apply_filters( 'edac_filter_fixes_settings_sections', $sections );

		// The callback should use the slug-based method name.
		$expected_callback = [ $this->fix, 'add_label_to_unlabelled_form_fields_section_callback' ];
		$this->assertEquals( $expected_callback, $sections['add_label_to_unlabelled_form_fields']['callback'] );
	}
}
