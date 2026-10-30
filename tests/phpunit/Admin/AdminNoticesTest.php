<?php
/**
 * Class EDACAdminNoticesTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Admin_Notices;
/**
 * Admin Notices test case.
 */
class AdminNoticesTest extends WP_UnitTestCase {
	/**
	 * Instance of the Admin_Notices class.
	 *
	 * @var Admin_Notices $admin_notices.
	 */
	private $admin_notices;
	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->admin_notices = new Admin_Notices();
	}

	/**
	 * Clean up hooks, the current screen and the current user after each test.
	 */
	protected function tearDown(): void {
		remove_action( 'admin_notices', [ $this->admin_notices, 'edac_review_notice' ] );
		remove_action( 'admin_notices', [ $this->admin_notices, 'edac_black_friday_notice' ] );
		remove_action( 'admin_notices', [ $this->admin_notices, 'edac_gaad_presale_notice' ] );
		remove_action( 'admin_notices', [ $this->admin_notices, 'edac_gaad_sale_notice' ] );

		unset( $GLOBALS['current_screen'] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Test that the edac_get_black_friday_message function exists.
	 */
	public function test_edac_get_black_friday_message_exists() {
		$this->assertTrue(
			method_exists( $this->admin_notices, 'edac_get_black_friday_message' ),
			'Class does not have method edac_get_black_friday_message'
		);
	}
	/**
	 * Test that the edac_get_black_friday_message function returns a string.
	 */
	public function test_edac_get_black_friday_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_black_friday_message() );
	}
	/**
	 * Set the stored license state the Black Friday segments are derived from.
	 *
	 * @param string $status The edacp_license_status value.
	 * @param string $plan   The plan stored in the license metadata, or an empty string for none.
	 */
	private function set_license_state( $status, $plan = '' ) {
		update_option( 'edacp_license_status', $status );
		if ( '' !== $plan ) {
			update_option( 'edac_license_metadata', [ 'plan' => $plan ] );
		}
	}
	/**
	 * Test that a site without Pro active is Free, unless the stored license is expired.
	 */
	public function test_black_friday_segment_is_free_without_pro() {
		if ( defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'EDACP_VERSION is already defined in this environment.' );
		}
		$this->assertSame( 'free', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'agency' );
		$this->assertSame( 'free', $this->admin_notices->edac_get_black_friday_segment() );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'save 20% and get a free course', $message );
		$this->assertStringContainsString( '<span aria-hidden="true">🎉</span>', $message );
		$this->assertStringContainsString( 'BlackFriday26-free-presale', $message );
		$this->assertStringNotContainsString( 'pre-sale-questions/?discount', $message );
		$this->assertStringContainsString( 'BlackFriday26-free-pricing', $message );
		$this->assertStringContainsString( 'discount=BF2674B96A3C', $message );
		$this->set_license_state( 'expired', 'professional' );
		$this->assertSame( 'lapsed', $this->admin_notices->edac_get_black_friday_segment() );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Welcome back', $message );
		$this->assertStringContainsString( 'BlackFriday26-lapsed-presale', $message );
		$this->assertStringContainsString( 'BlackFriday26-lapsed-renew', $message );
		$this->assertStringContainsString( 'discount=BF2674B96A3C', $message );
	}

	/**
	 * Test that each license state maps to the right Black Friday segment when Pro is active.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_black_friday_segments_with_pro() {
		define( 'EDACP_VERSION', '1.0.0' );
		// Pro active with no license state yet gets no notice.
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'expired' );
		$this->assertSame( 'lapsed', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'expired', 'professional' );
		$this->assertSame( 'lapsed', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'expired', 'agency' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_message() );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'expired', 'enterprise' );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'professional' );
		$this->assertSame( 'professional', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'small-business' );
		$this->assertSame( 'smb', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'agency' );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'enterprise' );
		$this->assertSame( 'agency', $this->admin_notices->edac_get_black_friday_segment() );
		$this->set_license_state( 'valid', 'small-business-plus' );
		$this->assertSame( 'smb', $this->admin_notices->edac_get_black_friday_segment() );
		// A valid license with no recorded plan yet, or an unrecognised one, gets no notice.
		$this->set_license_state( 'valid', 'unknown' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_segment() );
		delete_option( 'edac_license_metadata' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_segment() );
	}

	/**
	 * Test the copy and tracking for each paid segment, and that agency shows no notice.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_black_friday_message_per_paid_segment() {
		define( 'EDACP_VERSION', '1.0.0' );
		$this->set_license_state( 'valid', 'professional' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Small Business', $message );
		$this->assertStringContainsString( 'BlackFriday26-pro-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF26856B22FF', $message );
		$this->assertStringContainsString( 'pro=true', $message );
		$this->set_license_state( 'valid', 'small-business' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'Upgrade &amp; Save 25%', $message );
		$this->assertStringContainsString( 'BlackFriday26-smb-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF2622E6189C', $message );
		// SMB+ gets the Small Business notice.
		$this->set_license_state( 'valid', 'small-business-plus' );
		$message = $this->admin_notices->edac_get_black_friday_message();
		$this->assertStringContainsString( 'BlackFriday26-smb-upgrade', $message );
		$this->assertStringContainsString( 'discount=BF2622E6189C', $message );
		$this->set_license_state( 'valid', 'agency' );
		$this->assertSame( '', $this->admin_notices->edac_get_black_friday_message() );
	}

	/**
	 * Test that removing admin notices does not error when the current screen is unavailable.
	 */
	public function test_edac_remove_admin_notices_handles_missing_screen() {
		global $current_screen;
		$previous_screen = $current_screen ?? null;
		$current_screen  = null;
		try {
			$this->admin_notices->edac_remove_admin_notices();
			$this->assertTrue( true );
		} finally {
			$current_screen = $previous_screen;
		}
	}
	/**
	 * Test that GAAD pre-sale message method exists.
	 */
	public function test_edac_get_gaad_presale_message_exists() {
		$this->assertTrue( method_exists( $this->admin_notices, 'edac_get_gaad_presale_message' ) );
	}
	/**
	 * Test that GAAD pre-sale message method returns a string.
	 */
	public function test_edac_get_gaad_presale_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_gaad_presale_message() );
	}
	/**
	 * Test pre-sale copy for non-pro users.
	 */
	public function test_edac_get_gaad_presale_message_non_pro_copy() {
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on Accessibility Checker Pro', $message );
		$this->assertStringContainsString( '3 days only', $message );
		$this->assertStringContainsString( 'View Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/accessibility-checker/pricing/', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringNotContainsString( 'View Course Pricing', $message );
	}
	/**
	 * Test pre-sale copy for pro users.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_presale_message_pro_copy() {
		if ( defined( 'EDAC_KEY_VALID' ) || defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test Pro-message branch: EDAC_KEY_VALID or EDACP_VERSION is already defined in this environment.' );
		}
		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on accessibility courses and ArchiveWP', $message );
		$this->assertStringContainsString( '3 days only', $message );
		$this->assertStringContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringContainsString( 'View Course Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/archivewp/', $message );
		$this->assertStringContainsString( 'equalizedigital.com/learn/courses/', $message );
	}
	/**
	 * Test that pro plugin without valid key gets non-pro pre-sale copy.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_presale_message_pro_without_valid_key_shows_non_pro_copy() {
		define( 'EDACP_VERSION', '1.0.0' );
		$message = $this->admin_notices->edac_get_gaad_presale_message();
		$this->assertStringContainsString( 'Starting May 20th: Save 15% on Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'View Pricing', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
	}
	/**
	 * Test that GAAD sale message method exists.
	 */
	public function test_edac_get_gaad_sale_message_exists() {
		$this->assertTrue( method_exists( $this->admin_notices, 'edac_get_gaad_sale_message' ) );
	}
	/**
	 * Test that GAAD sale message method returns a string.
	 */
	public function test_edac_get_gaad_sale_message_returns_string() {
		$this->assertIsString( $this->admin_notices->edac_get_gaad_sale_message() );
	}
	/**
	 * Test sale copy for non-pro users.
	 */
	public function test_edac_get_gaad_sale_message_non_pro_copy() {
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( '3 days only: Save 15% when you upgrade to Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'Limited-time offer', $message );
		$this->assertStringContainsString( 'Ends May 22nd', $message );
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringContainsString( 'equalizedigital.com/accessibility-checker/pricing/?discount=GAAD2026', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringNotContainsString( 'View Course Pricing', $message );
	}
	/**
	 * Test sale copy for pro users.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_sale_message_pro_copy() {
		if ( defined( 'EDAC_KEY_VALID' ) || defined( 'EDACP_VERSION' ) ) {
			$this->markTestSkipped( 'Cannot test Pro-message branch: EDAC_KEY_VALID or EDACP_VERSION is already defined in this environment.' );
		}
		define( 'EDACP_VERSION', '1.0.0' );
		define( 'EDAC_KEY_VALID', true );
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( 'Global Accessibility Awareness Day Flash Sale', $message );
		$this->assertStringContainsString( 'Save 15% on accessibility courses and ArchiveWP', $message );
		$this->assertStringContainsString( 'Limited-time offer', $message );
		$this->assertStringContainsString( 'Ends May 22nd', $message );
		$this->assertStringContainsString( 'View ArchiveWP Pricing', $message );
		$this->assertStringContainsString( 'View Course Pricing', $message );
		$this->assertStringContainsString( 'equalizedigital.com/archivewp/?discount=GAAD2026', $message );
		$this->assertStringContainsString( 'equalizedigital.com/learn/courses/?discount=GAAD2026', $message );
	}
	/**
	 * Test that pro plugin without valid key gets non-pro sale copy.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_edac_get_gaad_sale_message_pro_without_valid_key_shows_non_pro_copy() {
		define( 'EDACP_VERSION', '1.0.0' );
		$message = $this->admin_notices->edac_get_gaad_sale_message();
		$this->assertStringContainsString( '3 days only: Save 15% when you upgrade to Accessibility Checker Pro', $message );
		$this->assertStringContainsString( 'Upgrade Now', $message );
		$this->assertStringNotContainsString( 'View ArchiveWP Pricing', $message );
	}
	/**
	 * Test that GAAD AJAX handlers are registered.
	 */
	public function test_gaad_ajax_hooks_are_registered() {
		$this->admin_notices->init_hooks();
		$this->assertSame( 10, has_action( 'wp_ajax_edac_gaad_presale_notice_ajax', [ $this->admin_notices, 'edac_gaad_presale_notice_ajax' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_edac_gaad_sale_notice_ajax', [ $this->admin_notices, 'edac_gaad_sale_notice_ajax' ] ) );
	}
	/**
	 * Test that the review notice stays hidden once it has been dismissed for good.
	 */
	public function test_review_notice_stays_hidden_when_the_option_is_stop() {
		wp_cache_flush();
		delete_transient( 'edac_review_notice_reminder' );
		update_option( 'edac_review_notice', 'stop' );
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ) );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertSame( 'stop', get_option( 'edac_review_notice' ) );
	}
	/**
	 * Test that the notice is shown, and the option set to play, on a site active for over 14 days.
	 */
	public function test_review_notice_prompts_on_a_site_active_for_more_than_two_weeks() {
		wp_cache_flush();
		delete_option( 'edac_review_notice' );
		delete_transient( 'edac_review_notice_reminder' );
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s', strtotime( '-20 days' ) ) );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertSame( 'play', get_option( 'edac_review_notice' ) );
		$this->assertStringContainsString( 'edac-review-notice', $output );
		$this->assertStringContainsString( 'Write A Review', $output );
	}
	/**
	 * Test that a fresh install pauses the notice and sets the two week reminder transient.
	 */
	public function test_review_notice_pauses_and_sets_a_reminder_on_a_new_site() {
		wp_cache_flush();
		delete_option( 'edac_review_notice' );
		delete_transient( 'edac_review_notice_reminder' );
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertSame( 'pause', get_option( 'edac_review_notice' ) );
		// The transient round-trips through the options table as the string '1', not a boolean.
		$this->assertNotFalse( get_transient( 'edac_review_notice_reminder' ) );
	}
	/**
	 * Test that a paused notice is shown again once its reminder transient has expired.
	 */
	public function test_review_notice_resumes_when_the_reminder_transient_expires() {
		wp_cache_flush();
		update_option( 'edac_review_notice', 'pause' );
		delete_transient( 'edac_review_notice_reminder' );
		update_option( 'edac_activation_date', gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertSame( 'play', get_option( 'edac_review_notice' ) );
		$this->assertStringContainsString( 'edac-review-notice', $output );
	}
	/**
	 * Test that the notice stays hidden while the two week reminder transient is still set.
	 */
	public function test_review_notice_stays_hidden_during_the_reminder_window() {
		wp_cache_flush();
		delete_option( 'edac_review_notice' );
		set_transient( 'edac_review_notice_reminder', true, DAY_IN_SECONDS );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
		$this->assertFalse( get_option( 'edac_review_notice' ) );
	}
	/**
	 * Test that the notice markup is rendered once the option is set to play.
	 */
	public function test_review_notice_renders_when_the_option_is_play() {
		wp_cache_flush();
		update_option( 'edac_review_notice', 'play' );
		delete_transient( 'edac_review_notice_reminder' );

		ob_start();
		$this->admin_notices->edac_review_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'edac-review-notice', $output );
		$this->assertStringContainsString( 'Never Ask Again', $output );
	}
	/**
	 * Test that notices are removed on the plugin's own admin screens.
	 */
	public function test_edac_remove_admin_notices_clears_notices_on_plugin_screens() {
		$callback = static function () {
			return 'test notice';
		};
		add_action( 'admin_notices', $callback );
		add_action( 'all_admin_notices', $callback );

		set_current_screen( 'toplevel_page_accessibility_checker' );
		$this->admin_notices->edac_remove_admin_notices();

		$this->assertFalse( has_action( 'admin_notices', $callback ) );
		$this->assertFalse( has_action( 'all_admin_notices', $callback ) );
	}
	/**
	 * Test that notices registered elsewhere are left in place on other screens.
	 */
	public function test_edac_remove_admin_notices_keeps_notices_on_other_screens() {
		$callback = static function () {
			return 'test notice';
		};
		add_action( 'admin_notices', $callback );

		set_current_screen( 'dashboard' );
		$this->admin_notices->edac_remove_admin_notices();

		$this->assertSame( 10, has_action( 'admin_notices', $callback ) );
	}
	/**
	 * Test that a non array value from the screens filter leaves every notice in place.
	 */
	public function test_edac_remove_admin_notices_ignores_a_non_array_screen_filter() {
		$callback = static function () {
			return 'test notice';
		};
		$filter   = static function () {
			return 'not-an-array';
		};
		add_action( 'admin_notices', $callback );
		add_filter( 'edac_filter_remove_admin_notices_screens', $filter );

		set_current_screen( 'toplevel_page_accessibility_checker' );

		try {
			$this->admin_notices->edac_remove_admin_notices();
			$this->assertSame( 10, has_action( 'admin_notices', $callback ) );
		} finally {
			remove_filter( 'edac_filter_remove_admin_notices_screens', $filter );
		}
	}
	/**
	 * Test that the notice hooks are added for a user who can see the plugin notices.
	 */
	public function test_hook_notices_registers_notices_for_a_user_who_can_edit_posts() {
		$user_id = self::factory()->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $user_id );

		$this->admin_notices->hook_notices();

		$this->assertSame( 10, has_action( 'admin_notices', [ $this->admin_notices, 'edac_review_notice' ] ) );
		$this->assertSame( 10, has_action( 'admin_notices', [ $this->admin_notices, 'edac_black_friday_notice' ] ) );
	}
	/**
	 * Test that the notice hooks are skipped for a user without the required capability.
	 */
	public function test_hook_notices_skips_users_without_the_capability() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$this->admin_notices->hook_notices();

		$this->assertFalse( has_action( 'admin_notices', [ $this->admin_notices, 'edac_review_notice' ] ) );
		$this->assertFalse( has_action( 'admin_notices', [ $this->admin_notices, 'edac_black_friday_notice' ] ) );
	}
}
