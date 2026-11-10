<?php
/**
 * Tests for the welcome page rendering contracts.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\OptIn\Email_Opt_In;
use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Welcome_Page;
use EqualizeDigital\AccessibilityChecker\Tests\TestHelpers\DatabaseHelpers;

/**
 * Tests for Welcome_Page::render_summary() and
 * Welcome_Page::maybe_render_email_opt_in().
 *
 * The welcome page is the first screen an admin sees after activating, and it
 * switches wholesale between three states: the Pro stats grid when the licence
 * is active, the free upsell banner when it is not, and nothing at all once the
 * banner has been dismissed. The branch is decided inside the renderer, so the
 * only way to know which state renders is to render it - a wrong condition here
 * shows Pro-only statistics, or an upsell, to the wrong user and nothing else
 * catches it.
 *
 * The Pro branch needs EDACP_VERSION defined and EDAC_KEY_VALID true, which the
 * free plugin's bootstrap fixes to false before the suite runs, so that branch
 * cannot be exercised from PHPUnit and is deliberately not asserted here.
 *
 * @covers \EDAC\Admin\Welcome_Page::render_summary
 * @covers \EDAC\Admin\Welcome_Page::maybe_render_email_opt_in
 */
class WelcomePageTest extends WP_UnitTestCase {

	/**
	 * The current user the per-user branches read.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * The email address given to the current user.
	 *
	 * @var string
	 */
	private $user_email = 'welcome-page@example.com';

	/**
	 * Create the plugin table once, outside any test transaction.
	 *
	 * The renderer builds its summary through Scans_Stats on every call, and
	 * those queries self-join the issues table, which MySQL disallows on the
	 * temporary tables WP_UnitTestCase would otherwise create.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		DatabaseHelpers::create_table();
	}

	/**
	 * Drop the plugin table.
	 *
	 * @return void
	 */
	public static function tear_down_after_class(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB -- Test-only table teardown.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}accessibility_checker" );

		parent::tear_down_after_class();
	}

	/**
	 * Start every test from an empty table, a known user and no stale cache.
	 *
	 * @return void
	 */
	public function setUp(): void {
		global $wpdb;
		parent::setUp();

		// DELETE rather than TRUNCATE so no implicit commit breaks test rollback.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}accessibility_checker" ); // phpcs:ignore WordPress.DB -- Test-only cleanup.

		update_option( 'edac_post_types', [ 'post', 'page' ] );
		delete_option( 'edacp_fullscan_completed_at' );
		( new Scans_Stats() )->clear_cache();

		$this->user_id = self::factory()->user->create(
			[
				'role'       => 'administrator',
				'user_email' => $this->user_email,
			]
		);
		wp_set_current_user( $this->user_id );
	}

	/**
	 * Restore the shared state.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		( new Scans_Stats() )->clear_cache();

		parent::tearDown();
	}

	/**
	 * Without an active licence the Pro stats grid is replaced by the upsell banner.
	 *
	 * @return void
	 */
	public function test_render_summary_shows_the_free_upsell_banner_without_pro() {
		$html = $this->render_summary();

		$this->assertStringContainsString( 'Site-Wide Accessibility Reports', $html );
		$this->assertStringContainsString( 'dismiss_welcome_cta', $html );
		$this->assertStringContainsString( 'Unlock Detailed Accessibility Reports', $html );
		$this->assertStringContainsString( 'accessibility-checker/pricing/', $html );
	}

	/**
	 * The banner and the stats grid are mutually exclusive, never both.
	 *
	 * @return void
	 */
	public function test_render_summary_does_not_show_the_stats_grid_alongside_the_banner() {
		$html = $this->render_summary();

		$this->assertStringNotContainsString( 'Passed Checks', $html );
		$this->assertStringNotContainsString( 'Average Issue Density', $html );
	}

	/**
	 * Dismissing the banner leaves only the wrapper the AJAX handler targets.
	 *
	 * The dismissal is stored per user; a boolean written through
	 * update_user_meta() reads back as the string '1', which is what the
	 * renderer's `true !== (bool) get_user_meta( ... )` test is written for.
	 *
	 * @return void
	 */
	public function test_render_summary_shows_no_banner_once_it_has_been_dismissed() {
		update_user_meta( $this->user_id, 'edac_welcome_cta_dismissed', true );

		$html = $this->render_summary();

		$this->assertStringContainsString( 'edac_welcome_page_summary', $html );
		$this->assertStringNotContainsString( 'Site-Wide Accessibility Reports', $html );
		$this->assertStringNotContainsString( 'dismiss_welcome_cta', $html );
	}

	/**
	 * While the opt-in modal is still pending the panel stays empty - the modal
	 * is what offers the opt-in at that point, and the user has not subscribed,
	 * so the seen-modal guard is the only thing that can be suppressing it.
	 *
	 * @return void
	 */
	public function test_maybe_render_email_opt_in_is_silent_while_the_modal_is_still_pending() {
		$this->assertSame( '', $this->render_email_opt_in() );
	}

	/**
	 * Once the modal has been seen and not acted on, the form is offered inline
	 * and pre-filled with the current user's own details.
	 *
	 * @return void
	 */
	public function test_maybe_render_email_opt_in_renders_the_form_once_the_modal_has_been_seen() {
		update_user_meta( $this->user_id, Email_Opt_In::EDAC_USER_OPTIN_SEEN_MODAL_KEY, true );

		$html = $this->render_email_opt_in();

		$this->assertStringContainsString( 'News in Your Inbox', $html );
		$this->assertStringContainsString( $this->user_email, $html );
	}

	/**
	 * A subscribed user is never offered the form, even in the one state where
	 * it would otherwise render - so the subscription guard alone is enough.
	 *
	 * @return void
	 */
	public function test_maybe_render_email_opt_in_is_silent_for_a_subscribed_user() {
		update_user_meta( $this->user_id, Email_Opt_In::EDAC_USER_OPTIN_SEEN_MODAL_KEY, true );
		update_user_meta( $this->user_id, Email_Opt_In::EDAC_USER_OPTIN_META_KEY, true );

		$this->assertSame( '', $this->render_email_opt_in() );
	}

	/**
	 * Render the welcome page summary and capture its markup.
	 *
	 * @return string
	 */
	private function render_summary(): string {
		ob_start();
		Welcome_Page::render_summary();

		return (string) ob_get_clean();
	}

	/**
	 * Render the opt-in panel and capture its markup.
	 *
	 * @return string
	 */
	private function render_email_opt_in(): string {
		ob_start();
		Welcome_Page::maybe_render_email_opt_in();

		return (string) ob_get_clean();
	}
}
