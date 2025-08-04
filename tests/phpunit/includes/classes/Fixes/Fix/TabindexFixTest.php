<?php
/**
 * Test class for TabindexFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\TabindexFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the TabindexFix class.
 */
class TabindexFixTest extends WP_UnitTestCase {

	/**
	 * Test that TabindexFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new TabindexFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'remove_tabindex', TabindexFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = TabindexFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Remove Tabindex from Focusable Elements', $nicename );
	}

	/**
	 * Test get_type returns frontend.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'frontend', TabindexFix::get_type() );
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fix    = new TabindexFix();
		$fields = $fix->get_fields_array();

		$this->assertIsArray( $fields );
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
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new TabindexFix();
		$existing_fields = [ 'existing_field' => [ 'type' => 'text' ] ];
		$fields          = $fix->get_fields_array( $existing_fields );

		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertArrayHasKey( 'edac_fix_remove_tabindex', $fields );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new TabindexFix();

		$fix->register();

		// Verify that the filter was added by checking if it has the expected callback.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 *
	 * @return void
	 */
	public function test_run_when_disabled() {
		$fix = new TabindexFix();

		// Ensure option is disabled.
		update_option( 'edac_fix_remove_tabindex', false );

		// Count filters before run.
		$filters_before = has_filter( 'edac_filter_frontend_fixes_data' );

		$fix->run();

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
		$fix = new TabindexFix();

		// Enable the option.
		update_option( 'edac_fix_remove_tabindex', true );

		$fix->run();

		// Check that the filter was added.
		$this->assertTrue( has_filter( 'edac_filter_frontend_fixes_data' ) !== false );
	}

	/**
	 * Test the frontend data filter when enabled.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter() {
		$fix = new TabindexFix();

		// Enable the option.
		update_option( 'edac_fix_remove_tabindex', true );

		$fix->run();

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
