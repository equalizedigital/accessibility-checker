<?php
/**
 * Tests for LinkUnderline class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\LinkUnderline;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
use WP_UnitTestCase;

/**
 * LinkUnderline test case
 */
class LinkUnderlineTest extends WP_UnitTestCase {

	/**
	 * Test fix instance
	 *
	 * @var LinkUnderline
	 */
	private $fix;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new LinkUnderline();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_force_link_underline' );
		
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
		$this->assertEquals( 'link_underline', LinkUnderline::get_slug() );
	}

	/**
	 * Test get_nicename method
	 */
	public function test_get_nicename() {
		$this->assertEquals( 'Add Underlines to all non-nav Links', LinkUnderline::get_nicename() );
	}

	/**
	 * Test get_fancyname method
	 */
	public function test_get_fancyname() {
		$this->assertEquals( 'Underline Links', LinkUnderline::get_fancyname() );
	}

	/**
	 * Test get_type method
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', LinkUnderline::get_type() );
	}

	/**
	 * Test register method adds settings fields filter
	 */
	public function test_register_adds_settings_fields_filter() {
		$this->fix->register();
		
		$this->assertNotFalse( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) );
	}

	/**
	 * Test get_fields_array method returns correct field
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();
		
		$this->assertArrayHasKey( 'edac_fix_force_link_underline', $fields );
		$field = $fields['edac_fix_force_link_underline'];
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Force Link Underline', $field['label'] );
		$this->assertEquals( 'force_link_underline', $field['labelledby'] );
		$this->assertEquals( 'Ensure that non-navigation links are underlined.', $field['description'] );
		$this->assertEquals( 'link_underline', $field['fix_slug'] );
		$this->assertEquals( 8489, $field['help_id'] );
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
		$this->assertArrayHasKey( 'edac_fix_force_link_underline', $fields );
		$this->assertEquals( 'text', $fields['existing_field']['type'] );
		$this->assertEquals( 'Existing Field', $fields['existing_field']['label'] );
	}

	/**
	 * Test run method does nothing when option is disabled
	 */
	public function test_run_does_nothing_when_disabled() {
		update_option( 'edac_fix_force_link_underline', false );
		
		$this->fix->run();
		
		// Verify no filters are registered.
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method registers frontend data filter when enabled
	 */
	public function test_run_registers_frontend_data_filter_when_enabled() {
		update_option( 'edac_fix_force_link_underline', true );
		
		$this->fix->run();
		
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method adds frontend data when enabled
	 */
	public function test_run_adds_frontend_data_when_enabled() {
		update_option( 'edac_fix_force_link_underline', true );
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		$this->assertArrayHasKey( 'underline', $data );
		$this->assertTrue( $data['underline']['enabled'] );
		$this->assertEquals( 'a', $data['underline']['target'] );
	}

	/**
	 * Test frontend data includes default target selector
	 */
	public function test_frontend_data_includes_default_target_selector() {
		update_option( 'edac_fix_force_link_underline', true );
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		$this->assertEquals( 'a', $data['underline']['target'] );
	}

	/**
	 * Test frontend data respects custom target selector filter
	 */
	public function test_frontend_data_respects_custom_target_selector_filter() {
		update_option( 'edac_fix_force_link_underline', true );
		
		// Add a filter to customize the target selector.
		add_filter(
			'edac_fix_underline_target',
			function ( $target ) {
				return $target . ':not(.no-underline)';
			} 
		);
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		$this->assertEquals( 'a:not(.no-underline)', $data['underline']['target'] );
		
		// Clean up.
		remove_all_filters( 'edac_fix_underline_target' );
	}

	/**
	 * Test edac_fix_underline_target filter is properly applied
	 */
	public function test_edac_fix_underline_target_filter_applied() {
		update_option( 'edac_fix_force_link_underline', true );
		
		$custom_selector = '.content a, .post a';
		
		add_filter(
			'edac_fix_underline_target',
			function () use ( $custom_selector ) {
				return $custom_selector;
			} 
		);
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		$this->assertEquals( $custom_selector, $data['underline']['target'] );
		
		// Clean up.
		remove_all_filters( 'edac_fix_underline_target' );
	}

	/**
	 * Test field structure matches expected pattern
	 */
	public function test_field_structure_matches_pattern() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_force_link_underline'];
		
		// Test required field properties.
		$required_properties = [ 'type', 'label', 'labelledby', 'description', 'fix_slug', 'help_id' ];
		
		foreach ( $required_properties as $property ) {
			$this->assertArrayHasKey( $property, $field, "Field missing required property: {$property}" );
		}
		
		// Test field values are non-empty strings.
		$this->assertIsString( $field['type'] );
		$this->assertNotEmpty( $field['type'] );
		$this->assertIsString( $field['label'] );
		$this->assertNotEmpty( $field['label'] );
		$this->assertIsInt( $field['help_id'] );
		$this->assertGreaterThan( 0, $field['help_id'] );
	}

	/**
	 * Test frontend data structure
	 */
	public function test_frontend_data_structure() {
		update_option( 'edac_fix_force_link_underline', true );
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		// Test that underline data has required properties.
		$this->assertArrayHasKey( 'underline', $data );
		$this->assertArrayHasKey( 'enabled', $data['underline'] );
		$this->assertArrayHasKey( 'target', $data['underline'] );
		
		// Test property types.
		$this->assertIsBool( $data['underline']['enabled'] );
		$this->assertIsString( $data['underline']['target'] );
		
		// Test that enabled is true when fix is active.
		$this->assertTrue( $data['underline']['enabled'] );
	}

	/**
	 * Test edac_fix_underline_target filter documentation
	 */
	public function test_edac_fix_underline_target_filter_documentation() {
		// This test ensures the filter is properly documented in the code.
		$reflection = new \ReflectionClass( $this->fix );
		$file_path  = $reflection->getFileName();
		
		// Only read local files, not remote URLs.
		if ( $file_path && is_readable( $file_path ) && ! filter_var( $file_path, FILTER_VALIDATE_URL ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
			$method_content = file_get_contents( $file_path );
			// Test that the filter is documented with proper docblock.
			$this->assertStringContainsString( '@hook edac_fix_underline_target', $method_content );
			$this->assertStringContainsString( '@param string $el', $method_content );
			$this->assertStringContainsString( '@return string', $method_content );
			$this->assertStringContainsString( '@since 1.16.0', $method_content );
		} else {
			$this->markTestSkipped( 'Cannot read fix file for documentation check' );
		}
	}

	/**
	 * Test multiple filter applications
	 */
	public function test_multiple_filter_applications() {
		update_option( 'edac_fix_force_link_underline', true );
		
		// Add multiple filters with different priorities.
		add_filter(
			'edac_fix_underline_target',
			function ( $target ) {
				return $target . ':not(.nav)';
			},
			10 
		);
		
		add_filter(
			'edac_fix_underline_target',
			function ( $target ) {
				return $target . ':not(.button)';
			},
			20 
		);
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		// Should include both filter modifications.
		$this->assertEquals( 'a:not(.nav):not(.button)', $data['underline']['target'] );
		
		// Clean up.
		remove_all_filters( 'edac_fix_underline_target' );
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
		$this->assertTrue( method_exists( $this->fix, 'get_type' ) );
		$this->assertTrue( method_exists( $this->fix, 'register' ) );
		$this->assertTrue( method_exists( $this->fix, 'run' ) );
	}

	/**
	 * Test option handling edge cases
	 */
	public function test_option_handling_edge_cases() {
		// Test with null option value.
		delete_option( 'edac_fix_force_link_underline' );
		$this->fix->run();
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
		
		// Test with string "false".
		update_option( 'edac_fix_force_link_underline', '0' );
		$this->fix->run();
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
		
		// Test with string "true".
		update_option( 'edac_fix_force_link_underline', '1' );
		$this->fix->run();
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}
}
