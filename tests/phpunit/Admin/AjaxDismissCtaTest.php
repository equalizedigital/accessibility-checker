<?php
/**
 * AJAX call-to-action dismissal behaviour tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;

/**
 * Tests for the welcome page and dashboard widget CTA dismissal AJAX handlers.
 *
 * Both handlers are fire-and-forget writes with a contract spread over three
 * places: the admin JS (src/admin/index.js) posts the action and only acts on a
 * body that decodes to the exact string `success`; the handler stores a user
 * meta flag; and the two renderers (admin/class-welcome-page.php and
 * admin/class-widgets.php) read that flag back through `(bool) get_user_meta( ... )`
 * to decide whether the call to action is still shown. Nothing asserted the meta
 * key, the current-user scoping or the response shape before this file.
 *
 * @covers \EDAC\Admin\Ajax::dismiss_welcome_cta
 * @covers \EDAC\Admin\Ajax::dismiss_dashboard_cta
 */
class AjaxDismissCtaTest extends WP_Ajax_UnitTestCase {

	/**
	 * AJAX handler under test.
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * User the dismissal is performed as.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Create the shared user fixture for this test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );

		$this->ajax = new Ajax();
		add_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', [ $this->ajax, 'dismiss_welcome_cta' ] );
		add_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this->ajax, 'dismiss_dashboard_cta' ] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', [ $this->ajax, 'dismiss_welcome_cta' ] );
		remove_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this->ajax, 'dismiss_dashboard_cta' ] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Dismissing a call to action records it for the current user only.
	 *
	 * The body has to decode to the bare string `success`: the welcome page JS
	 * hides the summary panel only when `response === 'success'`, so switching
	 * the handler to wp_send_json_success() (an object body) would leave the CTA
	 * on screen even though the dismissal had been stored.
	 *
	 * @dataProvider data_cta_handlers
	 *
	 * @param string $action      The `wp_ajax_` action name the admin JS posts.
	 * @param string $meta_key    User meta key this handler is expected to set.
	 * @param string $sibling_key User meta key belonging to the other CTA.
	 * @return void
	 */
	public function test_dismiss_cta_records_the_dismissal_for_the_current_user( string $action, string $meta_key, string $sibling_key ): void {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$this->assertSame( 'success', json_decode( $this->_last_response, true ) );

		// The renderers only ever test this flag for truthiness, so the assertion
		// mirrors the production read rather than pinning the stored value.
		$this->assertTrue(
			(bool) get_user_meta( self::$admin_id, $meta_key, true ),
			'The dismissal must be stored under the meta key the renderers read.'
		);

		// Dismissing one CTA must not dismiss the other.
		$this->assertSame( '', get_user_meta( self::$admin_id, $sibling_key, true ) );

		// Nor may it write to anyone else's account.
		$other_user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->assertSame( '', get_user_meta( $other_user_id, $meta_key, true ) );
	}

	/**
	 * Data provider for the CTA dismissal handlers.
	 *
	 * @return array<string, array{string, string, string}>
	 */
	public static function data_cta_handlers(): array {
		return [
			'welcome CTA'   => [ 'edac_dismiss_welcome_cta_ajax', 'edac_welcome_cta_dismissed', 'edac_dashboard_cta_dismissed' ],
			'dashboard CTA' => [ 'edac_dismiss_dashboard_cta_ajax', 'edac_dashboard_cta_dismissed', 'edac_welcome_cta_dismissed' ],
		];
	}

	/**
	 * Both dismissal handlers are registered on their own wp_ajax_ actions.
	 *
	 * @return void
	 */
	public function test_dismiss_cta_hooks_are_registered(): void {
		$this->ajax->init_hooks();

		$this->assertSame( 10, has_action( 'wp_ajax_edac_dismiss_welcome_cta_ajax', [ $this->ajax, 'dismiss_welcome_cta' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_edac_dismiss_dashboard_cta_ajax', [ $this->ajax, 'dismiss_dashboard_cta' ] ) );
	}
}
