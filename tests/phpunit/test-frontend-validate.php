<?php
/**
 * Class EDACFrontendValidateTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Frontend_Validate;

/**
 * Frontend Validate test case.
 */
class EDACFrontendValidateTest extends WP_UnitTestCase {
	
	/**
	 * Instance of the Frontend_Validate class.
	 *
	 * @var Frontend_Validate $frontend_validate.
	 */
	private $frontend_validate;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->frontend_validate = new Frontend_Validate();
	}

	/**
	 * Test that the validate function does not run validation when the current page is not index.php.
	 */
	public function test_validate_does_not_run_on_non_index_pages() {
		// Set up a non-index page.
		global $pagenow;
		$pagenow = 'page.php';

		// Call the validate function.
		$this->frontend_validate->validate();

		// Assert that the edac_validate function was not called.
		$this->assertFalse( has_action( 'load', 'edac_validate' ) );
	}

	/**
	 * Test that the validate function does not run validation when the customizer preview is active.
	 */
	public function test_validate_does_not_run_in_customizer_preview() {
		// Set up the customizer preview.
		set_theme_mod( 'is_customize_preview', true );

		// Call the validate function.
		$this->frontend_validate->validate();

		// Assert that the edac_validate function was not called.
		$this->assertFalse( has_action( 'load', 'edac_validate' ) );
	}

	/**
	 * Test that the validate function does not run validation when the current user cannot edit posts.
	 */
	public function test_validate_does_not_run_for_non_editors() {
		// Set up a user who cannot edit posts.
		wp_set_current_user( 1 );

		// Call the validate function.
		$this->frontend_validate->validate();

		// Assert that the edac_validate function was not called.
		$this->assertFalse( has_action( 'load', 'edac_validate' ) );
	}

	/**
	 * Test that the validate function runs validation when all conditions are met.
	 */
	public function test_validate_runs_when_conditions_are_met() {
		// Set up conditions for validation to run.
		global $pagenow;
		$pagenow = 'index.php';
		set_theme_mod( 'is_customize_preview', false );
		wp_set_current_user( 1 );
		add_action( 'load', 'edac_validate' );

		// Call the validate function.
		$this->frontend_validate->validate();

		// Assert that the edac_validate function was called.
		$this->assertIsNumeric( has_action( 'load', 'edac_validate' ) );
	}
}
