<?php
/**
 * Tests for the admin notice dismissal handlers and the render gate they set.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Admin_Notices;

/**
 * Tests for the dismissal side of the admin notices.
 *
 * AdminNoticesTest covers the notice copy. This covers the rest of the notice lifecycle in
 * admin/class-admin-notices.php: the gate that stops the Black Friday notice rendering once it has
 * been dismissed, and the four wp_ajax_* handlers the notice buttons call.
 *
 * @covers \EDAC\Admin\Admin_Notices::edac_black_friday_notice
 * @covers \EDAC\Admin\Admin_Notices::edac_black_friday_notice_ajax
 * @covers \EDAC\Admin\Admin_Notices::edac_gaad_presale_notice_ajax
 * @covers \EDAC\Admin\Admin_Notices::edac_gaad_sale_notice_ajax
 * @covers \EDAC\Admin\Admin_Notices::edac_review_notice_ajax
 * @covers \EDAC\Admin\Admin_Notices::set_fixes_transient_on_save
 */
class AdminNoticesDismissalTest extends WP_Ajax_UnitTestCase {

	/**
	 * Options the code under test reads or writes.
	 *
	 * @var string[]
	 */
	private static $notice_options = [
		'edac_black_friday_2026_notice_dismiss',
		'edac_black_friday_2025_notice_dismiss',
		'edac_gaad_presale_notice_dismiss_2026',
		'edac_gaad_sale_notice_dismiss_2026',
		'edac_gaad_notice_dismiss_2026',
		'edac_review_notice',
		'edacp_license_status',
	];

	/**
	 * Instance of the class under test.
	 *
	 * @var Admin_Notices
	 */
	private $admin_notices;

	/**
	 * The date_i18n filter a test pinned the clock with, if it pinned one.
	 *
	 * @var callable|null
	 */
	private $pinned_date_filter;

	/**
	 * Set up the fixture.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->clear_notice_state();

		$this->admin_notices = new Admin_Notices();
		$this->admin_notices->init_hooks();
	}

	/**
	 * Remove the hooks and the state the tests wrote.
	 */
	protected function tearDown(): void {
		$this->clear_notice_state();

		if ( null !== $this->pinned_date_filter ) {
			remove_filter( 'date_i18n', $this->pinned_date_filter, 10 );
		}

		remove_action( 'wp_ajax_edac_black_friday_notice_ajax', [ $this->admin_notices, 'edac_black_friday_notice_ajax' ] );
		remove_action( 'wp_ajax_edac_gaad_presale_notice_ajax', [ $this->admin_notices, 'edac_gaad_presale_notice_ajax' ] );
		remove_action( 'wp_ajax_edac_gaad_sale_notice_ajax', [ $this->admin_notices, 'edac_gaad_sale_notice_ajax' ] );
		remove_action( 'wp_ajax_edac_review_notice_ajax', [ $this->admin_notices, 'edac_review_notice_ajax' ] );
		remove_action( 'updated_option', [ $this->admin_notices, 'set_fixes_transient_on_save' ] );

		parent::tearDown();
	}

	/**
	 * Delete every option and transient the code under test touches.
	 *
	 * The dismissal options are read first and the handler returns early when one is set, so a
	 * leftover from an earlier test would silently take the other branch.
	 *
	 * @return void
	 */
	private function clear_notice_state(): void {
		foreach ( self::$notice_options as $option ) {
			delete_option( $option );
		}

		delete_transient( 'edac_review_notice_reminder' );
		delete_transient( 'edac_fixes_settings_saved' );
	}

	/**
	 * Fire one of the notice dismissal AJAX actions and decode the captured response.
	 *
	 * The base class builds $_REQUEST from the $_POST set here but does not reset $_last_response,
	 * so the body is cleared before each dispatch: otherwise a handler that produced no output
	 * leaves the previous test's response in place and the next assertion reads stale JSON.
	 *
	 * @param string $action  The wp_ajax action to fire.
	 * @param array  $request Values to place in $_POST. An empty array omits the nonce.
	 * @return array The decoded response body.
	 */
	private function dispatch_notice_ajax( string $action, array $request ): array {
		$_POST = $request;

		$this->_last_response = '';

		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $exception ) {
			// Every notice handler ends in wp_send_json_*, so a body is always produced.
			$this->assertNotEmpty( $this->_last_response );
		}

		return (array) json_decode( $this->_last_response, true );
	}

	/**
	 * Pin date_i18n() to a given Ymd date for the rest of one test.
	 *
	 * The Black Friday gate compares date_i18n( 'Ymd' ) against a hardcoded window, so without
	 * pinning the clock the in-window branch is unreachable for most of the year.
	 *
	 * @param string $date The date the notice code should see.
	 * @return void
	 */
	private function pin_date_i18n( string $date ): void {
		$this->pinned_date_filter = static function ( $formatted, $format ) use ( $date ) {
			return 'Ymd' === $format ? $date : $formatted;
		};

		add_filter( 'date_i18n', $this->pinned_date_filter, 10, 4 );
	}

	/**
	 * Data provider for the four notice dismissal actions.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_dismissal_actions(): array {
		return [
			'black friday' => [ 'edac_black_friday_notice_ajax' ],
			'gaad presale' => [ 'edac_gaad_presale_notice_ajax' ],
			'gaad sale'    => [ 'edac_gaad_sale_notice_ajax' ],
			'review'       => [ 'edac_review_notice_ajax' ],
		];
	}

	/**
	 * Data provider for the dismissal handlers that write a notice option.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_dismissal_state(): array {
		return [
			'black friday' => [ 'edac_black_friday_notice_ajax', 'edac_black_friday_2026_notice_dismiss', [ 'edac_black_friday_2025_notice_dismiss' ] ],
			'gaad presale' => [ 'edac_gaad_presale_notice_ajax', 'edac_gaad_presale_notice_dismiss_2026', [] ],
			'gaad sale'    => [ 'edac_gaad_sale_notice_ajax', 'edac_gaad_sale_notice_dismiss_2026', [ 'edac_gaad_notice_dismiss_2026' ] ],
		];
	}

	/**
	 * Data provider for the Black Friday promo window boundaries.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_black_friday_dates(): array {
		return [
			'day before the window' => [ '20261122', false ],
			'first day'             => [ '20261123', true ],
			'last day'              => [ '20261202', true ],
			'day after the window'  => [ '20261203', false ],
		];
	}

	/**
	 * Data provider for the review notice actions.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	public function provider_review_actions(): array {
		return [
			'dismissed' => [ 'stop', false ],
			'reminded'  => [ 'pause', true ],
		];
	}

	/**
	 * The four dismissal actions are registered by init_hooks().
	 *
	 * This is also the precondition for the dispatch tests below: an unregistered action fires
	 * nothing, and every response assertion would read an empty body.
	 */
	public function test_dismissal_ajax_actions_are_registered() {
		$actions = [ 'edac_black_friday_notice_ajax', 'edac_gaad_presale_notice_ajax', 'edac_gaad_sale_notice_ajax', 'edac_review_notice_ajax' ];

		foreach ( $actions as $action ) {
			$this->assertSame(
				10,
				has_action( 'wp_ajax_' . $action, [ $this->admin_notices, $action ] ),
				$action . ' is not registered by init_hooks().'
			);
		}
	}

	/**
	 * A request without a nonce is rejected by every dismissal handler.
	 *
	 * @dataProvider provider_dismissal_actions
	 *
	 * @param string $action The wp_ajax action being fired.
	 */
	public function test_dismissal_handlers_reject_a_missing_nonce( string $action ) {
		$response = $this->dispatch_notice_ajax( $action, [] );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Permission Denied', $this->_last_response );
	}

	/**
	 * A valid nonce records the dismissal and clears the earlier years' options.
	 *
	 * @dataProvider provider_dismissal_state
	 *
	 * @param string   $action The wp_ajax action being fired.
	 * @param string   $option The dismiss option the handler writes.
	 * @param string[] $legacy Options an earlier year's notice wrote.
	 */
	public function test_dismissal_handlers_record_the_dismissal( string $action, string $option, array $legacy ) {
		foreach ( $legacy as $legacy_option ) {
			update_option( $legacy_option, true );
		}

		$response = $this->dispatch_notice_ajax( $action, [ 'nonce' => wp_create_nonce( 'ajax-nonce' ) ] );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( (bool) get_option( $option ) );

		foreach ( $legacy as $legacy_option ) {
			$this->assertFalse( get_option( $legacy_option ), $legacy_option . ' was not cleared.' );
		}
	}

	/**
	 * A write that changes nothing reports the failed update error.
	 *
	 * The update_option() call returns false when the value it is given is already stored, which is
	 * the only way into the handler's second error branch: pre-setting the option makes the
	 * handler's own write a no-op. The legacy deletes run before that check, so they still clear.
	 *
	 * @dataProvider provider_dismissal_state
	 *
	 * @param string   $action The wp_ajax action being fired.
	 * @param string   $option The dismiss option the handler writes.
	 * @param string[] $legacy Options an earlier year's notice wrote.
	 */
	public function test_dismissal_handlers_report_a_write_that_changed_nothing( string $action, string $option, array $legacy ) {
		update_option( $option, true );

		foreach ( $legacy as $legacy_option ) {
			update_option( $legacy_option, true );
		}

		$response = $this->dispatch_notice_ajax( $action, [ 'nonce' => wp_create_nonce( 'ajax-nonce' ) ] );

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'Update option wasn', $this->_last_response );

		foreach ( $legacy as $legacy_option ) {
			$this->assertFalse( get_option( $legacy_option ), $legacy_option . ' was not cleared.' );
		}
	}

	/**
	 * The review handler needs a review_action as well as a nonce.
	 */
	public function test_review_notice_dismissal_requires_a_review_action() {
		$response = $this->dispatch_notice_ajax(
			'edac_review_notice_ajax',
			[ 'nonce' => wp_create_nonce( 'ajax-nonce' ) ]
		);

		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( 'The review action value was not set', $this->_last_response );
		$this->assertFalse( get_option( 'edac_review_notice' ) );
	}

	/**
	 * The chosen review action is stored, and only a pause sets the two week reminder.
	 *
	 * @dataProvider provider_review_actions
	 *
	 * @param string $action       The review_action value sent.
	 * @param bool   $has_reminder Whether a reminder transient is expected.
	 */
	public function test_review_notice_dismissal_stores_the_chosen_action( string $action, bool $has_reminder ) {
		$response = $this->dispatch_notice_ajax(
			'edac_review_notice_ajax',
			[
				'nonce'         => wp_create_nonce( 'ajax-nonce' ),
				'review_action' => $action,
			]
		);

		$reminder_is_set = (bool) get_transient( 'edac_review_notice_reminder' );

		$this->assertTrue( $response['success'] );
		$this->assertSame( $action, get_option( 'edac_review_notice' ) );
		$this->assertSame( $has_reminder, $reminder_is_set );
	}

	/**
	 * The Black Friday notice renders inside its promo window.
	 */
	public function test_black_friday_notice_renders_inside_the_promo_window() {
		$this->pin_date_i18n( '20261123' );

		$output = $this->render_black_friday_notice();

		$this->assertStringContainsString( 'edac_black_friday_notice', $output );
		$this->assertStringContainsString( 'Black Friday special', $output );
	}

	/**
	 * The Black Friday notice stays hidden once dismissed, even inside the promo window.
	 *
	 * The pinned date is the one the rendering test above uses, so empty output can only come from
	 * the dismissal gate.
	 */
	public function test_black_friday_notice_stays_hidden_when_dismissed() {
		$this->pin_date_i18n( '20261123' );
		update_option( 'edac_black_friday_2026_notice_dismiss', true );

		$this->assertSame( '', $this->render_black_friday_notice() );
	}

	/**
	 * The Black Friday window includes its first and last day and is closed either side.
	 *
	 * @dataProvider provider_black_friday_dates
	 *
	 * @param string $date     The date to pin date_i18n() to.
	 * @param bool   $rendered Whether the notice is expected to render.
	 */
	public function test_black_friday_notice_window_boundaries( string $date, bool $rendered ) {
		$this->pin_date_i18n( $date );

		$is_rendered = (bool) strpos( $this->render_black_friday_notice(), 'Black Friday special' );

		$this->assertSame( $rendered, $is_rendered );
	}

	/**
	 * Saving an option that is not one of the fix settings does not set the flag.
	 */
	public function test_fixes_transient_is_not_set_for_an_unrelated_option() {
		$this->admin_notices->set_fixes_transient_on_save( 'blogname' );

		$this->assertFalse( get_transient( 'edac_fixes_settings_saved' ) );
	}

	/**
	 * Render the Black Friday notice and return what it echoed.
	 *
	 * @return string
	 */
	private function render_black_friday_notice(): string {
		ob_start();
		$this->admin_notices->edac_black_friday_notice();

		return (string) ob_get_clean();
	}
}
