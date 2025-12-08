<?php
/**
 * Class ActivationTest
 *
 * Tests for the activation functionality including redirect logic
 *
 * @package Accessibility_Checker
 */

/**
 * Test case for activation redirect functionality.
 */
class ActivationTest extends WP_UnitTestCase {

	/**
	 * Clean up after each test
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_transient( 'edac_activation_redirect' );
		unset( $_GET['action'] );
		unset( $_GET['activate-multi'] );
	}

	/**
	 * Test that activation sets the redirect transient when not in multi-activation
	 */
	public function test_activation_sets_transient_for_single_activation() {
		// Ensure no multi-activation flags are set.
		unset( $_GET['action'] );
		unset( $_GET['activate-multi'] );

		// Call activation function.
		edac_activation();

		// Check that the transient was set.
		$this->assertEquals( 1, get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that activation does not set redirect transient during bulk activation
	 */
	public function test_activation_does_not_set_transient_for_bulk_activation() {
		// Simulate bulk activation.
		$_GET['action'] = 'activate-selected';

		// Call activation function.
		edac_activation();

		// Check that the transient was not set.
		$this->assertFalse( get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that activation does not set redirect transient when activate-multi is set
	 */
	public function test_activation_does_not_set_transient_for_activate_multi() {
		// Simulate activate-multi.
		$_GET['activate-multi'] = true;

		// Call activation function.
		edac_activation();

		// Check that the transient was not set.
		$this->assertFalse( get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that edac_is_multi_activation returns true when action is activate-selected
	 */
	public function test_is_multi_activation_detects_bulk_action() {
		$_GET['action'] = 'activate-selected';
		$this->assertTrue( edac_is_multi_activation() );
	}

	/**
	 * Test that edac_is_multi_activation returns true when activate-multi is set
	 */
	public function test_is_multi_activation_detects_activate_multi() {
		$_GET['activate-multi'] = true;
		$this->assertTrue( edac_is_multi_activation() );
	}

	/**
	 * Test that edac_is_multi_activation returns false for single activation
	 */
	public function test_is_multi_activation_returns_false_for_single_activation() {
		unset( $_GET['action'] );
		unset( $_GET['activate-multi'] );
		$this->assertFalse( edac_is_multi_activation() );
	}

	/**
	 * Test that edac_activation_redirect does nothing when transient is not set
	 */
	public function test_activation_redirect_does_nothing_without_transient() {
		// Ensure transient is not set.
		delete_transient( 'edac_activation_redirect' );

		// Mock current user with edit_posts capability.
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		// Call redirect function - should return without redirect.
		ob_start();
		edac_activation_redirect();
		$output = ob_get_clean();

		// Since there's no transient, function should return early.
		$this->assertEmpty( $output );
	}

	/**
	 * Test that activation redirect deletes transient when called
	 */
	public function test_activation_redirect_deletes_transient() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', 1, 30 );

		// Mock current user with edit_posts capability.
		$user_id = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		// Mock the redirect to avoid exit.
		add_filter( 'wp_redirect', '__return_false' );

		// Call redirect function.
		edac_activation_redirect();

		// Check that transient was deleted.
		$this->assertFalse( get_transient( 'edac_activation_redirect' ) );

		// Clean up.
		remove_filter( 'wp_redirect', '__return_false' );
	}

	/**
	 * Test that activation redirect does not redirect without proper capabilities
	 */
	public function test_activation_redirect_requires_edit_posts_capability() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', 1, 30 );

		// Mock current user without edit_posts capability.
		$user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		// Mock the redirect to check it's not called.
		$redirect_called = false;
		add_filter(
			'wp_redirect',
			function () use ( &$redirect_called ) {
				$redirect_called = true;
				return false;
			}
		);

		// Call redirect function.
		edac_activation_redirect();

		// Check that redirect was not called.
		$this->assertFalse( $redirect_called );

		// Clean up.
		remove_all_filters( 'wp_redirect' );
	}

	/**
	 * Test that activation sets required options
	 */
	public function test_activation_sets_required_options() {
		// Clear options first.
		delete_option( 'edac_activation_date' );
		delete_option( 'edac_post_types' );
		delete_option( 'edac_simplified_summary_position' );

		// Call activation.
		edac_activation();

		// Check that options are set.
		$this->assertNotFalse( get_option( 'edac_activation_date' ) );
		$this->assertEquals( [ 'post', 'page' ], get_option( 'edac_post_types' ) );
		$this->assertEquals( 'after', get_option( 'edac_simplified_summary_position' ) );
	}
}
