<?php
/**
 * Trait for common Fix class test methods.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Trait providing common test methods for Fix classes.
 * Handles most standard testing patterns to eliminate duplication.
 */
trait FixTestTrait {

	/**
	 * The fix instance to test.
	 *
	 * @var FixInterface
	 */
	protected $fix;

	/**
	 * Get the expected slug for this fix.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_expected_slug(): string;

	/**
	 * Get the expected type for this fix.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_expected_type(): string;

	/**
	 * Get the fix class name.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_fix_class_name(): string;

	/**
	 * Get the fix option names.
	 * Override if fix uses multiple options.
	 *
	 * @return array
	 */
	protected function get_fix_option_names(): array {
		return [ 'edac_fix_' . $this->get_expected_slug() ];
	}

	/**
	 * Common setup for fix tests.
	 *
	 * @return void
	 */
	public function common_setup() {
		// Clean up any existing options.
		foreach ( $this->get_fix_option_names() as $option_name ) {
			delete_option( $option_name );
		}
	}

	/**
	 * Common teardown for fix tests.
	 *
	 * @return void
	 */
	public function common_teardown() {
		// Clean up options.
		foreach ( $this->get_fix_option_names() as $option_name ) {
			delete_option( $option_name );
		}
		
		// Remove common filters to avoid interference.
		remove_all_filters( 'edac_filter_fixes_settings_sections' );
		remove_all_filters( 'edac_filter_fixes_settings_fields' );
		remove_all_filters( 'edac_filter_frontend_fixes_data' );
	}

	/**
	 * Test that the fix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$this->assertInstanceOf( FixInterface::class, $this->fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$class_name = $this->get_fix_class_name();
		$this->assertEquals( $this->get_expected_slug(), $class_name::get_slug() );
	}

	/**
	 * Test get_nicename returns non-empty string.
	 *
	 * @return void
	 */
	public function test_get_nicename_returns_non_empty_string() {
		$class_name = $this->get_fix_class_name();
		$nicename   = $class_name::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
	}

	/**
	 * Test get_type returns expected type.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$class_name = $this->get_fix_class_name();
		$this->assertEquals( $this->get_expected_type(), $class_name::get_type() );
	}

	/**
	 * Test get_fields_array returns array.
	 *
	 * @return void
	 */
	public function test_get_fields_array_returns_array() {
		$fields = $this->fix->get_fields_array();
		$this->assertIsArray( $fields );
	}

	/**
	 * Test register method adds settings fields filter.
	 *
	 * @return void
	 */
	public function test_register_adds_settings_fields_filter() {
		// Remove any existing filters to start clean.
		remove_all_filters( 'edac_filter_fixes_settings_fields' );
		
		$this->fix->register();
		
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields' ) !== false );
	}

	/**
	 * Test run method when option is disabled.
	 * Most fixes should do nothing when disabled.
	 *
	 * @return void
	 */
	public function test_run_when_option_disabled() {
		// Set all options to false.
		foreach ( $this->get_fix_option_names() as $option_name ) {
			update_option( $option_name, false );
		}

		// Count filters before run.
		$frontend_filters_before = has_filter( 'edac_filter_frontend_fixes_data' );
		
		$this->fix->run();
		
		// For most fixes, should not add frontend filter when disabled.
		$frontend_filters_after = has_filter( 'edac_filter_frontend_fixes_data' );
		$this->assertEquals( $frontend_filters_before, $frontend_filters_after );
	}

	/**
	 * Test run method when option is enabled.
	 * Most frontend fixes should add some kind of hook when enabled.
	 *
	 * @return void
	 */
	public function test_run_when_option_enabled() {
		// Set first option to true.
		$first_option = $this->get_fix_option_names()[0];
		update_option( $first_option, true );
		
		$this->fix->run();
		
		// This test mainly ensures the run method can be called without errors.
		// Individual test classes should override if they need specific assertions.
		$this->assertTrue( true );
	}

	/**
	 * Test that frontend data filter includes correct slug when enabled.
	 *
	 * Every fix declares whether it adds itself to the frontend fixes data payload
	 * ({@see fix_registers_frontend_data()}), so this asserts the behaviour either way
	 * instead of skipping the fixes that do not use it.
	 *
	 * @return void
	 */
	public function test_frontend_data_filter_includes_slug() {
		// Enable first option.
		$first_option = $this->get_fix_option_names()[0];
		update_option( $first_option, true );

		$this->fix->run();

		// Test the filter output.
		$data = apply_filters( 'edac_filter_frontend_fixes_data', [] );
		$key  = $this->get_frontend_data_key();

		if ( ! $this->fix_registers_frontend_data() ) {
			$this->assertArrayNotHasKey(
				$key,
				$data,
				'This fix should not add itself to the frontend fixes data payload.'
			);
			return;
		}

		$this->assertArrayHasKey( $key, $data );
		$this->assertArrayHasKey( 'enabled', $data[ $key ] );
		$this->assertTrue( $data[ $key ]['enabled'] );
	}

	/**
	 * Whether this fix adds itself to the frontend fixes data payload when its first
	 * option is enabled.
	 *
	 * Declared by each test class rather than detected at runtime: a fix that stops (or
	 * starts) adding itself to the payload fails this test instead of silently skipping it.
	 *
	 * @return bool
	 */
	protected function fix_registers_frontend_data(): bool {
		return 'frontend' === $this->get_expected_type();
	}

	/**
	 * The key this fix uses in the frontend fixes data payload.
	 *
	 * Defaults to the fix slug; override for fixes that use a different key.
	 *
	 * @return string
	 */
	protected function get_frontend_data_key(): string {
		return $this->get_expected_slug();
	}
}
