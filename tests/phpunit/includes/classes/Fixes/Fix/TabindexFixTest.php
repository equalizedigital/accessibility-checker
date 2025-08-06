<?php
/**
 * Test class for TabindexFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\TabindexFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the TabindexFix class.
 */
class TabindexFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new TabindexFix();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'remove_tabindex';
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
		return TabindexFix::class;
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();

		$this->assertArrayHasKey( 'edac_fix_remove_tabindex', $fields );

		$field = $fields['edac_fix_remove_tabindex'];
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Remove Tab Index', $field['label'] );
		$this->assertEquals( 'remove_tabindex', $field['labelledby'] );
		$this->assertEquals( 'remove_tabindex', $field['fix_slug'] );
		$this->assertEquals( 'Remove Tabindex from Focusable Elements', $field['group_name'] );
		$this->assertEquals( 8496, $field['help_id'] );
		$this->assertStringContainsString( 'tabindex', $field['description'] );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$this->fix->register();

		// Verify that the filter was added by checking if it has the expected callback.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_when_disabled() {
		// Ensure option is disabled.
		update_option( 'edac_fix_remove_tabindex', false );

		// Count filters before run.
		$filters_before = has_filter( 'edac_filter_frontend_fixes_data' );

		$this->fix->run();

		// Count filters after run - should be the same.
		$filters_after = has_filter( 'edac_filter_frontend_fixes_data' );

		$this->assertEquals( $filters_before, $filters_after );
	}

	/**
	 * Test run method when option is enabled.
	 *
	 * @return void
	 */
	public function test_run_when_enabled() {
		// Enable the option.
		update_option( 'edac_fix_remove_tabindex', true );

		$this->fix->run();

		// Check that the filter was added.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) !== false );
	}

	/**
	 * Test the frontend data filter when enabled.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter() {
		// Enable the option.
		update_option( 'edac_fix_remove_tabindex', true );

		$this->fix->run();

		// Test the filter output.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );

		$this->assertArrayHasKey( 'tabindex', $data );
		$this->assertArrayHasKey( 'enabled', $data['tabindex'] );
		$this->assertTrue( $data['tabindex']['enabled'] );
	}

	/**
	 * Clean up after tests.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'edac_fix_remove_tabindex' );
		parent::tearDown();
	}
}
