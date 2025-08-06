<?php
/**
 * Test class for SkipLinkFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\SkipLinkFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the SkipLinkFix class.
 */
class SkipLinkFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new SkipLinkFix();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'skip_link';
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
		return SkipLinkFix::class;
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();
		
		// Test main skip link field.
		$this->assertArrayHasKey( 'edac_fix_add_skip_link', $fields );
		$main_field = $fields['edac_fix_add_skip_link'];
		$this->assertEquals( 'checkbox', $main_field['type'] );
		$this->assertEquals( 'Enable Skip Link', $main_field['label'] );
		$this->assertEquals( 'add_skip_link', $main_field['labelledby'] );
		$this->assertEquals( 'skip_link', $main_field['section'] );
		$this->assertEquals( 'skip_link', $main_field['fix_slug'] );
		$this->assertEquals( 'Add Skip Links', $main_field['group_name'] );
		$this->assertEquals( 8638, $main_field['help_id'] );

		// Test target ID field.
		$this->assertArrayHasKey( 'edac_fix_add_skip_link_target_id', $fields );
		$target_field = $fields['edac_fix_add_skip_link_target_id'];
		$this->assertEquals( 'text', $target_field['type'] );
		$this->assertEquals( 'Main Content Target (required)', $target_field['label'] );
		$this->assertEquals( 'skip_link_target_id', $target_field['labelledby'] );
		$this->assertEquals( 'skip_link', $target_field['section'] );
		$this->assertEquals( 'edac_fix_add_skip_link', $target_field['condition'] );
		$this->assertEquals( 'edac_fix_add_skip_link', $target_field['required_when'] );
		$this->assertEquals( 'sanitize_text_field', $target_field['sanitize_callback'] );

		// Test navigation target field.
		$this->assertArrayHasKey( 'edac_fix_add_skip_link_nav_target_id', $fields );
		$nav_field = $fields['edac_fix_add_skip_link_nav_target_id'];
		$this->assertEquals( 'text', $nav_field['type'] );
		$this->assertEquals( 'Navigation Target', $nav_field['label'] );
		$this->assertEquals( 'skip_link_nav_target_id', $nav_field['labelledby'] );
		$this->assertEquals( 'skip_link', $nav_field['section'] );
		$this->assertEquals( 'edac_fix_add_skip_link', $nav_field['condition'] );
		$this->assertEquals( 'sanitize_text_field', $nav_field['sanitize_callback'] );
	}

	/**
	 * Test register method adds filters.
	 *
	 * @return void
	 */
	public function test_register_adds_filters() {
		$this->fix->register();

		// Verify that the filters were added.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_sections' ) !== false );
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_when_disabled() {
		// Ensure option is disabled.
		update_option( 'edac_fix_add_skip_link', false );

		$this->fix->run();

		// Check that no action was added.
		$this->assertFalse( has_action( 'wp_body_open', [ $this->fix, 'add_skip_link' ] ) );
	}

	/**
	 * Test run method when enabled but no targets set.
	 *
	 * @return void
	 */
	public function test_run_when_enabled_no_targets() {
		// Enable option but don't set targets.
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', '' );

		$this->fix->run();

		// Action should be added for the skip link.
		$this->assertTrue( has_action( 'wp_body_open', [ $this->fix, 'add_skip_link' ] ) !== false );
		
		// But no frontend data filter should be added.
		$this->assertFalse( has_filter( 'edac_filter_frontend_fixes_data' ) );
	}

	/**
	 * Test run method when enabled with targets.
	 *
	 * @return void
	 */
	public function test_run_when_enabled_with_targets() {
		// Enable option and set targets.
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', 'main, #content, article' );

		$this->fix->run();

		// Action should be added for the skip link.
		$this->assertTrue( has_action( 'wp_body_open', [ $this->fix, 'add_skip_link' ] ) !== false );
		
		// Frontend data filter should be added.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) !== false );
	}

	/**
	 * Test the frontend data filter output.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter() {
		$fix = new SkipLinkFix();

		// Enable option and set targets.
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', 'main, #content, article' );

		$fix->run();

		// Test the filter output.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );

		$this->assertArrayHasKey( 'skip_link', $data );
		$this->assertArrayHasKey( 'enabled', $data['skip_link'] );
		$this->assertArrayHasKey( 'targets', $data['skip_link'] );
		$this->assertTrue( $data['skip_link']['enabled'] );
		
		$expected_targets = [ '#main', '#content', '#article' ];
		$this->assertEquals( $expected_targets, $data['skip_link']['targets'] );
	}

	/**
	 * Test target processing with various formats.
	 *
	 * @return void
	 */
	public function test_target_processing() {
		$fix = new SkipLinkFix();

		// Test with various target formats.
		update_option( 'edac_fix_add_skip_link', true );
		update_option( 'edac_fix_add_skip_link_target_id', ' main , #content,  article,  , #duplicate ' );

		$fix->run();

		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		
		$expected_targets = [ '#main', '#content', '#article', '#duplicate' ];
		$this->assertEquals( $expected_targets, $data['skip_link']['targets'] );
	}

	/**
	 * Test add_skip_link method with no targets.
	 *
	 * @return void
	 */
	public function test_add_skip_link_no_targets() {
		$fix = new SkipLinkFix();
		
		// Set no targets.
		update_option( 'edac_fix_add_skip_link_target_id', '' );
		update_option( 'edac_fix_add_skip_link_nav_target_id', '' );

		ob_start();
		$fix->add_skip_link();
		$output = ob_get_clean();

		// Should output nothing when no targets are set.
		$this->assertEmpty( $output );
	}

	/**
	 * Test add_skip_link method with targets.
	 *
	 * @return void
	 */
	public function test_add_skip_link_with_targets() {
		$fix = new SkipLinkFix();
		
		// Set targets.
		update_option( 'edac_fix_add_skip_link_target_id', 'main' );
		update_option( 'edac_fix_add_skip_link_nav_target_id', 'nav' );

		ob_start();
		$fix->add_skip_link();
		$output = ob_get_clean();

		// Should output template with skip links.
		$this->assertStringContainsString( '<template id="skip-link-template">', $output );
		$this->assertStringContainsString( 'Skip to content', $output );
		$this->assertStringContainsString( 'Skip to navigation', $output );
		$this->assertStringContainsString( 'href="#nav"', $output );
		$this->assertStringContainsString( 'edac-bypass-block', $output );
	}

	/**
	 * Test skip link section callback.
	 *
	 * @return void
	 */
	public function test_skip_link_section_callback() {
		$fix = new SkipLinkFix();

		ob_start();
		$fix->skip_link_section_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<p>', $output );
		$this->assertStringContainsString( 'skip link', $output );
		$this->assertStringContainsString( '<a href=', $output );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_add_skip_link' );
		delete_option( 'edac_fix_add_skip_link_target_id' );
		delete_option( 'edac_fix_add_skip_link_nav_target_id' );
		parent::tearDown();
	}
}
