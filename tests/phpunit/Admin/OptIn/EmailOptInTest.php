<?php
/**
 * Class EmailOptInTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\OptIn\Email_Opt_In;

/**
 * Test class for the email opt-in handler.
 *
 * @group optin
 */
class EmailOptInTest extends WP_UnitTestCase {

	const TEST_USER_EMAIL      = 'test@example.com';
	const TEST_USER_FIRST_NAME = 'Test';

	/**
	 * Hold the ID of the current user.
	 *
	 * @var int The ID of the current user.
	 */
	private $current_user_id;

	/**
	 * Set up the user for tests.
	 */
	protected function setUp(): void {
		$this->current_user_id = $this->factory()->user->create();

		if ( is_wp_error( $this->current_user_id ) ) {
			$this->fail( $this->current_user_id->get_error_message() );
		}

		wp_set_current_user( $this->current_user_id );
	}

	/**
	 * Delete the user to clean up after tests.
	 */
	protected function tearDown(): void {
		wp_delete_user( $this->current_user_id );
	}

	/**
	 * Test that the user_already_subscribed method returns false when the user has not subscribed.
	 */
	public function test_user_already_subscribed_returns_false_when_user_has_not_subscribed() {
		$this->assertFalse( Email_Opt_In::user_already_subscribed() );
	}

	/**
	 * Test that the user_already_subscribed method returns true when the user has subscribed.
	 */
	public function test_user_already_subscribed_returns_true_when_user_is_subscribed() {
		update_user_meta( $this->current_user_id, Email_Opt_In::EDAC_USER_OPTIN_META_KEY, true );
		$this->assertTrue( Email_Opt_In::user_already_subscribed() );
	}

	/**
	 * Test that the should_show_modal method returns true when the user has not seen the modal.
	 */
	public function test_should_show_modal_returns_true_when_user_has_not_seen_it_yet() {
		$this->assertTrue( Email_Opt_In::should_show_modal() );
	}

	/**
	 * Test that the should_show_modal method returns false when the user has seen the modal.
	 */
	public function test_should_show_modal_returns_false_when_user_has_seen_it() {
		update_user_meta( $this->current_user_id, Email_Opt_In::EDAC_USER_OPTIN_SEEN_MODAL_KEY, true );
		$this->assertFalse( Email_Opt_In::should_show_modal() );
	}

	/**
	 * Test that the enqueue_scripts method registers the scripts and styles needed for the opt-in modal.
	 */
	public function test_enqueue_scripts_registers_thickbox_when_user_has_not_seen_modal() {
		$email_opt_in = new Email_Opt_In();
		$email_opt_in->enqueue_scripts();
		$this->assertTrue( wp_script_is( 'email-opt-in-form', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'email-opt-in-form', 'enqueued' ) );
		// check that admin footer has been hooked and thickbox is enqueued.
		$this->assertEquals( 10, has_action( 'admin_footer', array( $email_opt_in, 'render_modal' ) ) );
		$this->assertTrue( wp_script_is( 'thickbox', 'enqueued' ) );
	}

	/**
	 * Test that the enqueue_scripts method does not add thickbox when the user has seen the modal.
	 */
	public function test_enqueue_scripts_does_not_add_thickbox_when_user_has_seen_modal() {
		update_user_meta( $this->current_user_id, Email_Opt_In::EDAC_USER_OPTIN_SEEN_MODAL_KEY, true );
		$email_opt_in = new Email_Opt_In();
		$email_opt_in->enqueue_scripts();
		$this->assertTrue( wp_script_is( 'email-opt-in-form', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'email-opt-in-form', 'enqueued' ) );

		// check that admin footer has NOT been hooked.
		$this->assertFalse( has_action( 'admin_footer', array( $email_opt_in, 'render_modal' ) ) );
	}

	/**
	 * Test that the modal markup renders.
	 */
	public function test_form_renders_modal() {
		$email_opt_in = new Email_Opt_In();
		$email_opt_in->render_modal();
		$this->expectOutputRegex( '/<div id="edac-opt-in-modal".*?/' );
	}

	/**
	 * Test that the form renders and contains the expected email and user first name.
	 */
	public function test_form_renders_and_contains_expected_email_and_user_name() {
		$maybe_existing_user = get_user_by( 'email', self::TEST_USER_EMAIL );
		// set the users first name to test that it is pre-filled in the form.
		if ( $maybe_existing_user && ! get_user_meta( $maybe_existing_user->ID, 'first_name', true ) ) {
			update_user_meta( $maybe_existing_user->ID, 'first_name', self::TEST_USER_FIRST_NAME );
		}

		$user_id = $maybe_existing_user ? $maybe_existing_user->ID : $this->factory()->user->create(
			array(
				'user_email' => self::TEST_USER_EMAIL,
				'first_name' => self::TEST_USER_FIRST_NAME,
			)
		);
		wp_set_current_user( $user_id );
		$email_opt_in = new Email_Opt_In();
		$email_opt_in->render_form();
		$this->expectOutputRegex( '/.*?value="' . self::TEST_USER_EMAIL . '".*?/' );
		$this->expectOutputRegex( '/.*?value="' . self::TEST_USER_FIRST_NAME . '".*?/' );
	}

	/**
	 * Test that the ajax handlers are registered.
	 */
	public function test_ajax_handlers_registered() {
		$email_opt_in = new Email_Opt_In();
		$email_opt_in->register_ajax_handlers();
		$this->assertEquals( 10, has_action( 'wp_ajax_edac_email_opt_in_ajax', array( $email_opt_in, 'handle_email_opt_in' ) ) );
		$this->assertEquals( 10, has_action( 'wp_ajax_edac_email_opt_in_closed_modal_ajax', array( $email_opt_in, 'handle_email_opt_in_closed_modal' ) ) );
	}

	/**
	 * Test that the email opt-in ajax handler creates the user meta.
	 */
	public function test_handle_email_opt_in() {
		// mock the action in the $_POST global.
		$_POST['action'] = 'edac_email_opt_in_ajax';
		$email_opt_in    = new Email_Opt_In();

		$this->filter_ajax_die_handler_to_exception_instead_of_actual_die();
		try {
			ob_start();
			$email_opt_in->handle_email_opt_in();
		} catch ( Exception $e ) {
			// We expected this, just clear the buffer.
			ob_end_clean();
		}

		$this->assertTrue(
			(bool) get_user_meta(
				get_current_user_id(),
				Email_Opt_In::EDAC_USER_OPTIN_META_KEY,
				true
			)
		);
	}

	/**
	 * Test that the email opt-in closed modal ajax handler creates the user meta.
	 */
	public function test_handle_email_opt_in_closed_modal() {
		// mock the action in the $_POST global.
		$_POST['action'] = 'handle_email_opt_in_closed_modal_ajax';
		$email_opt_in    = new Email_Opt_In();

		$this->filter_ajax_die_handler_to_exception_instead_of_actual_die();
		try {
			ob_start();
			$email_opt_in->handle_email_opt_in_closed_modal();
		} catch ( Exception $e ) {
			// We expected this, just clear the buffer.
			ob_end_clean();
		}

		$this->assertTrue(
			(bool) get_user_meta(
				get_current_user_id(),
				Email_Opt_In::EDAC_USER_OPTIN_SEEN_MODAL_KEY,
				true
			)
		);
	}

	/**
	 * Filter the wp_die handler to throw an exception instead of actually dying,
	 * so that we can test the ajax handlers.
	 */
	private function filter_ajax_die_handler_to_exception_instead_of_actual_die() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			array( $this, 'throw_exception_instead_of_dying' ),
			1,
			1
		);
	}

	/**
	 * Throw an exception instead of actually dying.
	 *
	 * @return Closure
	 */
	public function throw_exception_instead_of_dying() {
		return function ( $message ) {
			// phpcs:ignore WordPress.Security -- this is just a test.
			throw new Exception( $message );
		};
	}
}
