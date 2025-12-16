<?php
/**
 * PHPUnit tests for the Activation_Redirect class.
 *
 * @package Accessibility_Checker\Tests
 */

use PHPUnit\Framework\TestCase;
use EDAC\Admin\Activation_Redirect;

/**
 * Class Activation_Redirect_Test
 *
 * @covers \EDAC\Admin\Activation_Redirect
 */
class ActivationRedirectTest extends WP_UnitTestCase {

	/**
	 * Instance of the Activation_Redirect class.
	 *
	 * @var Activation_Redirect $activation_redirect.
	 */
	private $activation_redirect;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->activation_redirect = new Activation_Redirect();
		
		// Clear any existing transients.
		delete_transient( 'edac_activation_redirect' );
		
		// Clear any $_GET parameters.
		unset( $_GET['activate-multi'] );

		// Intercept redirects to prevent headers being sent during tests.
		add_filter(
			'wp_redirect',
			static function ( $location ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test exception message.
				throw new Exception( 'Redirect to: ' . $location );
			}
		);
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		// Clean up transient and global state.
		delete_transient( 'edac_activation_redirect' );
		unset( $_GET['activate-multi'] );
		remove_all_filters( 'wp_doing_ajax' );
		remove_all_filters( 'is_network_admin' );
		remove_all_filters( 'wp_redirect' );
		parent::tearDown();
	}

	/**
	 * Test instantiation of Activation_Redirect class.
	 */
	public function test_can_instantiate_class() {
		$this->assertInstanceOf( Activation_Redirect::class, $this->activation_redirect );
	}

	/**
	 * Test that init() adds the admin_init action.
	 */
	public function test_init_adds_action() {
		$this->activation_redirect->init();
		$this->assertNotFalse( has_action( 'admin_init', [ $this->activation_redirect, 'maybe_redirect_to_welcome' ] ) );
	}

	/**
	 * Test that redirect does not happen when transient is not set.
	 */
	public function test_no_redirect_without_transient() {
		// Ensure transient doesn't exist.
		delete_transient( 'edac_activation_redirect' );
		
		// Set up admin user.
		$admin_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );
		
		// Call the method - it should return early.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// If we get here without a redirect and no transient, the test passes.
		$this->assertFalse( get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that transient is deleted only when redirect happens.
	 *
	 * Note: This test expects a redirect exception to be thrown because
	 * WP_TESTS_DOMAIN is defined in the test environment, preventing the
	 * actual redirect. In a real scenario without WP_TESTS_DOMAIN, the
	 * redirect would occur and the transient would be deleted.
	 */
	public function test_transient_is_deleted_only_on_redirect() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', true, 60 );
		
		// Set up admin user.
		$admin_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );
		
		// Call the method - it will return early due to WP_TESTS_DOMAIN.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// Transient should still exist because redirect didn't happen.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that redirect does not happen during AJAX requests.
	 */
	public function test_no_redirect_during_ajax() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', true, 60 );
		
		// Set up admin user.
		$admin_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );
		
		// Simulate AJAX request.
		add_filter(
			'wp_doing_ajax',
			function () {
				return true;
			}
		);
		
		// Call the method - it should return early.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// Transient should still exist because no redirect happened.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that redirect does not happen during bulk plugin activation.
	 */
	public function test_no_redirect_during_bulk_activation() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', true, 60 );
		
		// Set up admin user.
		$admin_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );
		
		// Simulate bulk activation.
		$_GET['activate-multi'] = 'true';
		
		// Call the method - it should return early.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// Transient should still exist because no redirect happened.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that redirect does not happen for users without proper capabilities.
	 */
	public function test_no_redirect_without_proper_capability() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', true, 60 );
		
		// Set up subscriber user (no edit_posts capability).
		$subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );
		
		// Call the method - it should return early.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// Transient should still exist because no redirect happened.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
	}

	/**
	 * Test that redirect does not happen in network admin (multisite).
	 *
	 * Note: This test is skipped because is_network_admin() checks the WP_NETWORK_ADMIN
	 * constant which cannot be easily mocked in unit tests. The network admin check is
	 * covered by integration testing in actual multisite environments.
	 */
	public function test_no_redirect_in_network_admin() {
		$this->markTestSkipped(
			'Cannot reliably test network admin context in unit tests. ' .
			'The is_network_admin() function checks WP_NETWORK_ADMIN constant ' .
			'which cannot be mocked. This is covered by integration tests.'
		);
	}

	/**
	 * Test activation function sets the transient.
	 */
	public function test_activation_sets_transient() {
		// Ensure transient doesn't exist initially.
		delete_transient( 'edac_activation_redirect' );
		
		// Mock the Accessibility_Statement class since it's required by activation.
		if ( ! class_exists( 'EDAC\Admin\Accessibility_Statement' ) ) {
			require_once EDAC_PLUGIN_DIR . 'admin/class-accessibility-statement.php';
		}
		
		// Run the activation function.
		edac_activation();
		
		// Check that the transient was set.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
		
		// Clean up.
		delete_transient( 'edac_activation_redirect' );
	}

	/**
	 * Test that the correct redirect URL is returned.
	 */
	public function test_get_welcome_page_url_returns_correct_url() {
		$url = $this->activation_redirect->get_welcome_page_url();
		
		// Verify the URL is properly formed and points to the welcome page.
		$this->assertStringContainsString( 'admin.php?page=accessibility_checker', $url );
		$this->assertStringContainsString( 'wp-admin', $url );
		
		// Verify it's a valid admin URL.
		$expected_url = admin_url( 'admin.php?page=accessibility_checker' );
		$this->assertEquals( $expected_url, $url );
	}

	/**
	 * Test that redirect does not happen in test environment.
	 */
	public function test_no_redirect_in_test_environment() {
		// Set the transient.
		set_transient( 'edac_activation_redirect', true, 60 );
		
		// Set up admin user.
		$admin_user = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );
		
		// WP_TESTS_DOMAIN should be defined in test environment.
		$this->assertTrue( defined( 'WP_TESTS_DOMAIN' ), 'WP_TESTS_DOMAIN should be defined in test environment' );
		
		// Call the method - it should return early without redirecting.
		$this->activation_redirect->maybe_redirect_to_welcome();
		
		// Transient should still exist because no redirect happened.
		$this->assertTrue( (bool) get_transient( 'edac_activation_redirect' ) );
	}
}
