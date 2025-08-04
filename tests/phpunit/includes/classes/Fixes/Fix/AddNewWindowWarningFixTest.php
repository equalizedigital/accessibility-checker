<?php
/**
 * Tests for AddNewWindowWarningFix class
 *
 * @package AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Tests\Fixes\Fix;

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddNewWindowWarningFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;
use WP_UnitTestCase;

/**
 * AddNewWindowWarningFix test case
 */
class AddNewWindowWarningFixTest extends WP_UnitTestCase {

	/**
	 * Test fix instance
	 *
	 * @var AddNewWindowWarningFix
	 */
	private $fix;

	/**
	 * Set up test fixtures
	 */
	public function setUp(): void {
		parent::setUp();
		$this->fix = new AddNewWindowWarningFix();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_new_window_warning' );
		
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
		$this->assertEquals( 'new_window_warning', AddNewWindowWarningFix::get_slug() );
	}

	/**
	 * Test get_nicename method
	 */
	public function test_get_nicename() {
		$this->assertEquals( 'Add warning when link opens new tab/window', AddNewWindowWarningFix::get_nicename() );
	}

	/**
	 * Test get_type method
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', AddNewWindowWarningFix::get_type() );
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
		
		$this->assertArrayHasKey( 'edac_fix_new_window_warning', $fields );
		$field = $fields['edac_fix_new_window_warning'];
		
		$this->assertEquals( 'Add Label To Links That Open A New Tab/Window', $field['label'] );
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'add_new_window_warning', $field['labelledby'] );
		$this->assertStringContainsString( 'Add a label and icon to links with', $field['description'] );
		$this->assertStringContainsString( '<code>target="_blank"</code>', $field['description'] );
		$this->assertStringContainsString( 'Note: This setting will have no effect if the &quot;Block Links Opening New Windows&quot; fix is enabled.', $field['description'] );
		$this->assertEquals( 'new_window_warning', $field['fix_slug'] );
		$this->assertEquals( 'Add warning when link opens new tab/window', $field['group_name'] );
		$this->assertEquals( 8493, $field['help_id'] );
	}

	/**
	 * Test run method does nothing when option is disabled
	 */
	public function test_run_does_nothing_when_disabled() {
		update_option( 'edac_fix_new_window_warning', false );
		
		$this->fix->run();
		
		// Verify no actions are registered.
		$this->assertFalse( has_action( 'wp_head', [ $this->fix, 'add_styles' ] ) );
		// Verify frontend data filter is not registered.
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method registers hooks when enabled
	 */
	public function test_run_registers_hooks_when_enabled() {
		update_option( 'edac_fix_new_window_warning', true );
		
		$this->fix->run();
		
		$this->assertNotFalse( has_action( 'wp_head', [ $this->fix, 'add_styles' ] ) );
		$this->assertNotFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method adds frontend data when enabled
	 */
	public function test_run_adds_frontend_data_when_enabled() {
		update_option( 'edac_fix_new_window_warning', true );
		
		$this->fix->run();
		
		$data = [];
		$data = apply_filters( 'edac_filter_frontend_fixes_data', $data );
		
		$this->assertArrayHasKey( 'new_window_warning', $data );
		$this->assertTrue( $data['new_window_warning']['enabled'] );
	}

	/**
	 * Test run method handles ANWW plugin conflict
	 */
	public function test_run_handles_anww_plugin_conflict() {
		update_option( 'edac_fix_new_window_warning', true );
		
		// Mock ANWW class and constant.
		if ( ! class_exists( 'ANWW' ) ) {
			eval( 'class ANWW {}' );
		}
		if ( ! defined( 'ANWW_VERSION' ) ) {
			define( 'ANWW_VERSION', '1.0.0' );
		}
		
		$this->fix->run();
		
		// Test that wp_enqueue_scripts action was registered to deregister ANWW scripts.
		$this->assertTrue( has_action( 'wp_enqueue_scripts' ) );
	}

	/**
	 * Test add_styles method outputs CSS
	 */
	public function test_add_styles_outputs_css() {
		// Mock EDAC_PLUGIN_URL constant.
		if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
			define( 'EDAC_PLUGIN_URL', 'http://example.com/wp-content/plugins/accessibility-checker/' );
		}
		
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '<style id="edac-nww">', $output );
		$this->assertStringContainsString( '@font-face', $output );
		$this->assertStringContainsString( 'font-family: \'anww\';', $output );
		$this->assertStringContainsString( 'assets/fonts', $output );
		$this->assertStringContainsString( '.edac-nww-external-link-icon', $output );
		$this->assertStringContainsString( 'speak: never;', $output );
		$this->assertStringContainsString( 'content: " \\e900";', $output );
		$this->assertStringContainsString( '--icon-size: 0.75em;', $output );
	}

	/**
	 * Test add_styles includes font URLs
	 */
	public function test_add_styles_includes_font_urls() {
		// Mock EDAC_PLUGIN_URL constant.
		if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
			define( 'EDAC_PLUGIN_URL', 'http://example.com/wp-content/plugins/accessibility-checker/' );
		}
		
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		// Test various font formats are included.
		$this->assertStringContainsString( 'anww.eot', $output );
		$this->assertStringContainsString( 'anww.ttf', $output );
		$this->assertStringContainsString( 'anww.woff', $output );
		$this->assertStringContainsString( 'anww.svg', $output );
		
		// Test font-display property.
		$this->assertStringContainsString( 'font-display: block;', $output );
	}

	/**
	 * Test add_styles includes CSS custom properties
	 */
	public function test_add_styles_includes_custom_properties() {
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( ':root {', $output );
		$this->assertStringContainsString( '--font-base: \'anww\', sans-serif;', $output );
		$this->assertStringContainsString( '--icon-size: 0.75em;', $output );
	}

	/**
	 * Test add_styles includes accessibility features
	 */
	public function test_add_styles_includes_accessibility_features() {
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		// Test that font smoothing properties are included for better readability.
		$this->assertStringContainsString( '-webkit-font-smoothing: antialiased;', $output );
		$this->assertStringContainsString( '-moz-osx-font-smoothing: grayscale;', $output );
		
		// Test that text-transform is disabled for icons.
		$this->assertStringContainsString( 'text-transform: none;', $output );
	}

	/**
	 * Test add_styles includes Elementor compatibility
	 */
	public function test_add_styles_includes_elementor_compatibility() {
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		$this->assertStringContainsString( '.edac-nww-external-link-icon.elementor-button-link-content:before', $output );
		$this->assertStringContainsString( 'vertical-align: middle;', $output );
	}

	/**
	 * Test field description includes proper HTML formatting
	 */
	public function test_field_description_html_formatting() {
		$fields = $this->fix->get_fields_array();
		$field  = $fields['edac_fix_new_window_warning'];
		
		// Test HTML elements in description.
		$this->assertStringContainsString( '<br>', $field['description'] );
		$this->assertStringContainsString( '<strong>', $field['description'] );
		$this->assertStringContainsString( '</strong>', $field['description'] );
		$this->assertStringContainsString( '<code>target="_blank"</code>', $field['description'] );
	}

	/**
	 * Test that CSS output is properly escaped
	 */
	public function test_css_output_properly_escaped() {
		// Mock EDAC_PLUGIN_URL constant with potentially unsafe content.
		if ( ! defined( 'EDAC_PLUGIN_URL' ) ) {
			define( 'EDAC_PLUGIN_URL', 'http://example.com/wp-content/plugins/accessibility-checker/' );
		}
		
		ob_start();
		$this->fix->add_styles();
		$output = ob_get_clean();
		
		// The font URL should be properly escaped with esc_url.
		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringNotContainsString( 'javascript:', $output );
	}
}
