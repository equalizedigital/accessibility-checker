<?php
/**
 * Tests for FocusOutlineFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\FocusOutlineFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
use WP_UnitTestCase;

/**
 * FocusOutlineFix test case
 */
class FocusOutlineFixTest extends WP_UnitTestCase {

	/**
	 * Test fix instance
	 *
	 * @var FocusOutlineFix
	 */
	private $fix;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new FocusOutlineFix();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_focus_outline' );
		
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
		$this->assertEquals( 'focus_outline', FocusOutlineFix::get_slug() );
	}

	/**
	 * Test get_nicename method
	 */
	public function test_get_nicename() {
		$this->assertEquals( 'Add Focus Outlines', FocusOutlineFix::get_nicename() );
	}

	/**
	 * Test get_type method
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', FocusOutlineFix::get_type() );
	}

	/**
	 * Test register method adds settings sections
	 */
	public function test_register_adds_settings_sections() {
		$sections = [];
		$this->fix->register();
		
		$sections = apply_filters( 'edac_filter_fixes_settings_sections', $sections );
		
		$this->assertArrayHasKey( 'focus_outline', $sections );
		$this->assertEquals( 'Focus Outline', $sections['focus_outline']['title'] );
		$this->assertEquals( 'Add an outline to elements when they receive keyboard focus.', $sections['focus_outline']['description'] );
		$this->assertEquals( [ $this->fix, 'focus_outline_section_callback' ], $sections['focus_outline']['callback'] );
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
		
		$this->assertArrayHasKey( 'edac_fix_focus_outline', $fields );
		$field = $fields['edac_fix_focus_outline'];
		
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Add Focus Outline', $field['label'] );
		$this->assertEquals( 'fix_focus_outline', $field['labelledby'] );
		$this->assertEquals( 'Add an outline to elements when they receive keyboard focus.', $field['description'] );
		$this->assertEquals( 'focus_outline', $field['section'] );
		$this->assertEquals( 'focus_outline', $field['fix_slug'] );
		$this->assertEquals( 8495, $field['help_id'] );
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
		$this->assertArrayHasKey( 'edac_fix_focus_outline', $fields );
		$this->assertEquals( 'text', $fields['existing_field']['type'] );
		$this->assertEquals( 'Existing Field', $fields['existing_field']['label'] );
	}

	/**
	 * Test run method does nothing when option is disabled
	 */
	public function test_run_does_nothing_when_disabled() {
		update_option( 'edac_fix_focus_outline', false );
		
		$this->fix->run();
		
		// Verify no actions are registered.
		$this->assertFalse( has_action( 'wp_head', [ $this->fix, 'css' ] ) );
	}

	/**
	 * Test run method registers CSS when enabled
	 */
	public function test_run_registers_css_when_enabled() {
		update_option( 'edac_fix_focus_outline', true );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_action( 'wp_head', [ $this->fix, 'css' ] ) );
	}

	/**
	 * Test css method outputs correct styles
	 */
	public function test_css_outputs_correct_styles() {
		ob_start();
		$this->fix->css();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '<style id="edac-fix-focus-outline">', $output );
		$this->assertStringContainsString( ':focus {', $output );
		$this->assertStringContainsString( 'outline: revert !important;', $output );
		$this->assertStringContainsString( 'outline-offset: revert !important;', $output );
		$this->assertStringContainsString( '}', $output );
		$this->assertStringContainsString( '</style>', $output );
	}

	/**
	 * Test css method uses proper CSS values
	 */
	public function test_css_uses_proper_css_values() {
		ob_start();
		$this->fix->css();
		$output = ob_get_clean();
		
		// Test that 'revert' is used to restore browser default focus styles.
		$this->assertStringContainsString( 'outline: revert !important;', $output );
		$this->assertStringContainsString( 'outline-offset: revert !important;', $output );
		
		// Test that !important is used to override theme styles
		$this->assertStringContainsString( '!important', $output );
	}

	/**
	 * Test css method includes proper style ID for targeting
	 */
	public function test_css_includes_proper_style_id() {
		ob_start();
		$this->fix->css();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( 'id="edac-fix-focus-outline"', $output );
	}

	/**
	 * Test focus_outline_section_callback method outputs description
	 */
	public function test_focus_outline_section_callback() {
		ob_start();
		$this->fix->focus_outline_section_callback();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '<p>', $output );
		$this->assertStringContainsString( 'Settings related to enhancing focus outlines for better keyboard accessibility.', $output );
		$this->assertStringContainsString( '</p>', $output );
	}

	/**
	 * Test that CSS selector targets all focusable elements
	 */
	public function test_css_targets_all_focusable_elements() {
		ob_start();
		$this->fix->css();
		$output = ob_get_clean();
		
		// Test that :focus pseudo-class is used (not element-specific selectors).
		$this->assertStringContainsString( ':focus {', $output );
		$this->assertStringNotContainsString( 'a:focus', $output );
		$this->assertStringNotContainsString( 'button:focus', $output );
		$this->assertStringNotContainsString( 'input:focus', $output );
	}

	/**
	 * Test that CSS uses modern properties
	 */
	public function test_css_uses_modern_properties() {
		ob_start();
		$this->fix->css();
		$output = ob_get_clean();
		
		// Test that both outline and outline-offset are addressed.
		$this->assertStringContainsString( 'outline:', $output );
		$this->assertStringContainsString( 'outline-offset:', $output );
		
		// Test that 'revert' value is used (CSS4 feature for restoring user agent styles).
		$this->assertStringContainsString( 'revert', $output );
	}

	/**
	 * Test field structure matches expected pattern
	 */
	public function test_field_structure_matches_pattern() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_focus_outline'];
		
		// Test required field properties.
		$required_properties = [ 'type', 'label', 'labelledby', 'description', 'section', 'fix_slug', 'help_id' ];
		
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
	 * Test integration with WordPress hooks system
	 */
	public function test_integration_with_wordpress_hooks() {
		// Test that register method properly sets up hooks.
		$this->fix->register();
		
		// Verify section filter is registered.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_sections' ) );
		
		// Verify fields filter is registered.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields' ) );
		
		// Test that run method conditionally registers wp_head hook.
		update_option( 'edac_fix_focus_outline', true );
		$this->fix->run();
		$this->assertNotFalse( has_action( 'wp_head', [ $this->fix, 'css' ] ) );
		
		// Clean up and test disabled state.
		remove_action( 'wp_head', [ $this->fix, 'css' ] );
		update_option( 'edac_fix_focus_outline', false );
		$this->fix->run();
		$this->assertFalse( has_action( 'wp_head', [ $this->fix, 'css' ] ) );
	}
}
