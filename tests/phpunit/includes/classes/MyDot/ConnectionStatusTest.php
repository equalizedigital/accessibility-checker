<?php
/**
 * Tests for email report connection status checks.
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\REST_Api;
use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\AccessibilityReportsPage;
use EqualizeDigital\AccessibilityChecker\MyDot\ConnectionStatus;
use EqualizeDigital\AccessibilityChecker\MyDot\Connector;

/**
 * Test cases for ConnectionStatus and the code that uses it.
 */
class ConnectionStatusTest extends WP_UnitTestCase {

	/**
	 * Option names reset around each test.
	 *
	 * @var string[]
	 */
	private const OPTIONS = [
		ConnectionStatus::LAST_ACTIVITY_OPTION,
		'edac_collection_interval_days',
		'edac_site_id',
		'edac_license_status',
		'edacp_license_key',
		'edac_jwt_public_key',
	];

	/**
	 * Reset state before each test.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->reset_state();

		// Default to a public site so results do not depend on WP_ENVIRONMENT_TYPE.
		// Tests that need a local site add their own filter, which runs after this one.
		add_filter( 'edac_is_local_site', '__return_false' );
	}

	/**
	 * Reset state after each test.
	 */
	public function tear_down(): void {
		$this->reset_state();
		remove_all_filters( 'edac_is_local_site' );
		remove_all_filters( 'pre_http_request' );
		parent::tear_down();
	}

	/**
	 * Remove saved options and the cached htpasswd result.
	 */
	private function reset_state(): void {
		foreach ( self::OPTIONS as $option ) {
			delete_option( $option );
		}
		delete_transient( ConnectionStatus::BASIC_AUTH_TRANSIENT );
	}

	/**
	 * Mock the response for all HTTP requests.
	 *
	 * @param int   $code    Response code.
	 * @param array $headers Response headers.
	 */
	private function mock_http( int $code, array $headers = [] ): void {
		add_filter(
			'pre_http_request',
			function () use ( $code, $headers ) {
				return [
					'headers'  => new WpOrg\Requests\Utility\CaseInsensitiveDictionary( $headers ),
					'body'     => '',
					'response' => [
						'code'    => $code,
						'message' => '',
					],
					'cookies'  => [],
				];
			}
		);
	}

	/**
	 * Host names and whether each one counts as local.
	 *
	 * @return array
	 */
	public function local_host_provider(): array {
		return [
			'localhost'          => [ 'localhost', true ],
			'upper case'         => [ 'LOCALHOST', true ],
			'.local suffix'      => [ 'mysite.local', true ],
			'.test suffix'       => [ 'mysite.test', true ],
			'.localhost suffix'  => [ 'mysite.localhost', true ],
			'loopback IP'        => [ '127.0.0.1', true ],
			'private IP'         => [ '192.168.1.20', true ],
			'IPv6 loopback'      => [ '[::1]', true ],
			'public domain'      => [ 'example.com', false ],
			'suffix in the name' => [ 'local.example.com', false ],
			'similar suffix'     => [ 'mysite.locals', false ],
			'public IP'          => [ '8.8.8.8', false ],
		];
	}

	/**
	 * Tests local host detection.
	 *
	 * @dataProvider local_host_provider
	 *
	 * @param string $host     Host name.
	 * @param bool   $expected Expected result.
	 */
	public function test_is_local_host( string $host, bool $expected ) {
		$this->assertSame( $expected, ConnectionStatus::is_local_host( $host ) );
	}

	/**
	 * Tests that the filter can override local detection.
	 */
	public function test_is_local_site_can_be_filtered() {
		add_filter( 'edac_is_local_site', '__return_false' );
		$this->assertFalse( ConnectionStatus::is_local_site() );

		add_filter( 'edac_is_local_site', '__return_true' );
		$this->assertTrue( ConnectionStatus::is_local_site() );
	}

	/**
	 * Tests that a 401 with a Basic challenge counts as htpasswd protection.
	 */
	public function test_basic_auth_detected_from_challenge() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );

		$this->assertTrue( ConnectionStatus::is_behind_basic_auth() );
	}

	/**
	 * Tests that Basic is found as a later challenge but not in a quoted realm.
	 */
	public function test_basic_auth_matches_scheme_only() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Digest realm="Staging", Basic realm="Staging"' ] );
		$this->assertTrue( ConnectionStatus::is_behind_basic_auth() );

		delete_transient( ConnectionStatus::BASIC_AUTH_TRANSIENT );
		remove_all_filters( 'pre_http_request' );
		$this->mock_http( 401, [ 'www-authenticate' => 'Bearer realm="Basic API, basic"' ] );
		$this->assertFalse( ConnectionStatus::is_behind_basic_auth() );
	}

	/**
	 * Tests that the check requests the route My Dot pulls from.
	 */
	public function test_basic_auth_check_requests_stats_route() {
		$requested = '';
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) use ( &$requested ) {
				$requested = $url;
				return new WP_Error( 'http_request_failed', 'Loopback blocked' );
			},
			10,
			3
		);

		ConnectionStatus::is_behind_basic_auth();

		$this->assertSame( rest_url( 'accessibility-checker/v1/scans-stats' ), $requested );
	}

	/**
	 * Tests that a failed request is cached for less time than a real answer.
	 */
	public function test_failed_basic_auth_check_is_cached_briefly() {
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'http_request_failed', 'Loopback blocked' );
			}
		);

		ConnectionStatus::is_behind_basic_auth();

		$timeout = (int) get_option( '_transient_timeout_' . ConnectionStatus::BASIC_AUTH_TRANSIENT );
		$this->assertGreaterThan( time(), $timeout );
		$this->assertLessThanOrEqual( time() + 5 * MINUTE_IN_SECONDS, $timeout );
	}

	/**
	 * Tests responses that do not count as htpasswd protection.
	 */
	public function test_basic_auth_not_detected_without_basic_challenge() {
		$this->mock_http( 401 );
		$this->assertFalse( ConnectionStatus::is_behind_basic_auth() );

		delete_transient( ConnectionStatus::BASIC_AUTH_TRANSIENT );
		remove_all_filters( 'pre_http_request' );
		$this->mock_http( 200 );
		$this->assertFalse( ConnectionStatus::is_behind_basic_auth() );
	}

	/**
	 * Tests that a failed request is not treated as htpasswd protection.
	 */
	public function test_basic_auth_not_detected_when_request_fails() {
		add_filter(
			'pre_http_request',
			function () {
				return new WP_Error( 'http_request_failed', 'Loopback blocked' );
			}
		);

		$this->assertFalse( ConnectionStatus::is_behind_basic_auth() );
	}

	/**
	 * Tests that the htpasswd check only sends one request while cached.
	 */
	public function test_basic_auth_result_is_cached() {
		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return new WP_Error( 'http_request_failed', 'Loopback blocked' );
			}
		);

		ConnectionStatus::is_behind_basic_auth();
		ConnectionStatus::is_behind_basic_auth();

		$this->assertSame( 1, $requests );
	}

	/**
	 * Fire the shutdown hooks without ending the test output buffers.
	 */
	private function run_shutdown(): void {
		remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
		do_action( 'shutdown' );
		remove_action( 'shutdown', [ ConnectionStatus::class, 'record_activity' ] );
	}

	/**
	 * Tests that the first check starts the clock and shows no warning.
	 *
	 * The baseline write is deferred to shutdown, so the option is only saved
	 * after the shutdown hook runs.
	 */
	public function test_first_check_sets_baseline_and_is_not_overdue() {
		$this->assertFalse( ConnectionStatus::is_pull_overdue() );
		$this->assertFalse( get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );

		$this->run_shutdown();

		$this->assertGreaterThan( 0, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
	}

	/**
	 * Tests that a fresh activity time is not rewritten within the throttle window.
	 */
	public function test_record_activity_is_throttled() {
		$recent = time() - 10;
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, $recent );

		ConnectionStatus::record_activity();

		$this->assertSame( $recent, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );

		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 2 * HOUR_IN_SECONDS );
		ConnectionStatus::record_activity();

		$this->assertGreaterThanOrEqual( time() - 5, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
	}

	/**
	 * Tests that a deferred baseline write does not run twice on one request.
	 */
	public function test_deferred_baseline_write_is_throttled() {
		$this->assertFalse( ConnectionStatus::is_pull_overdue() );
		$this->assertFalse( ConnectionStatus::is_pull_overdue() );

		$this->run_shutdown();

		$baseline = (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION );
		$this->assertGreaterThan( 0, $baseline );
	}

	/**
	 * Tests the overdue limit with the default interval plus grace days.
	 */
	public function test_pull_overdue_uses_default_interval() {
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 8 * DAY_IN_SECONDS );
		$this->assertFalse( ConnectionStatus::is_pull_overdue() );

		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 10 * DAY_IN_SECONDS );
		$this->assertTrue( ConnectionStatus::is_pull_overdue() );
	}

	/**
	 * Tests that the interval sent by My Dot is used.
	 */
	public function test_pull_overdue_uses_saved_interval() {
		update_option( 'edac_collection_interval_days', 30 );
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 10 * DAY_IN_SECONDS );

		$this->assertFalse( ConnectionStatus::is_pull_overdue() );
	}

	/**
	 * Tests which issue is returned when more than one could apply.
	 */
	public function test_get_issue_priority() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 30 * DAY_IN_SECONDS );

		$this->assertSame( ConnectionStatus::ISSUE_BASIC_AUTH, ConnectionStatus::get_issue( true ) );

		add_filter( 'edac_is_local_site', '__return_true' );
		$this->assertSame( ConnectionStatus::ISSUE_LOCAL, ConnectionStatus::get_issue( true ) );
	}

	/**
	 * Tests that a protected site shows no issue while pulls are arriving.
	 */
	public function test_get_issue_ignores_basic_auth_when_pulls_are_arriving() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );
		ConnectionStatus::record_activity();

		$this->assertSame( '', ConnectionStatus::get_issue( true ) );
	}

	/**
	 * Tests that a protected site is flagged before it connects.
	 */
	public function test_get_issue_flags_basic_auth_before_connecting() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );

		$this->assertSame( ConnectionStatus::ISSUE_BASIC_AUTH, ConnectionStatus::get_issue( false ) );
	}

	/**
	 * Tests that an overdue pull only matters on a connected site.
	 */
	public function test_get_issue_stale_requires_connection() {
		$this->mock_http( 200 );
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 30 * DAY_IN_SECONDS );

		$this->assertSame( '', ConnectionStatus::get_issue( false ) );
		$this->assertSame( ConnectionStatus::ISSUE_STALE, ConnectionStatus::get_issue( true ) );
	}

	/**
	 * Tests that clear removes saved data.
	 */
	public function test_clear_removes_saved_data() {
		ConnectionStatus::record_activity();
		set_transient( ConnectionStatus::BASIC_AUTH_TRANSIENT, 'yes', HOUR_IN_SECONDS );

		ConnectionStatus::clear();

		$this->assertFalse( get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
		$this->assertFalse( get_transient( ConnectionStatus::BASIC_AUTH_TRANSIENT ) );
	}

	/**
	 * Build a stats request with a token signed by a new key, and save the public key.
	 *
	 * @return WP_REST_Request
	 */
	private function build_signed_stats_request(): WP_REST_Request {
		$key = openssl_pkey_new(
			[
				'private_key_bits' => 2048,
				'private_key_type' => OPENSSL_KEYTYPE_RSA,
			]
		);
		update_option( 'edac_jwt_public_key', openssl_pkey_get_details( $key )['key'] );

		$encode  = function ( string $value ): string {
			return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- building a test token.
		};
		$message = $encode( wp_json_encode( [ 'alg' => 'RS256' ] ) ) . '.' . $encode(
			wp_json_encode(
				[
					'iss' => Connector::get_jwt_issuer(),
					'aud' => Connector::get_jwt_audience(),
					'exp' => time() + HOUR_IN_SECONDS,
				]
			)
		);
		openssl_sign( $message, $signature, $key, OPENSSL_ALGO_SHA256 );

		$request = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats' );
		$request->set_header( 'Authorization', 'Bearer ' . $message . '.' . $encode( $signature ) );

		return $request;
	}

	/**
	 * Tests that a valid My Dot token records report activity.
	 */
	public function test_valid_token_records_activity() {
		update_option( 'edac_site_id', 'site-1' );

		$this->assertTrue( ( new REST_Api() )->can_get_scans_stats( $this->build_signed_stats_request() ) );
		$this->assertGreaterThan( 0, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
	}

	/**
	 * Tests that a logged in user viewing stats does not count as a pull.
	 */
	public function test_logged_in_user_does_not_record_activity() {
		update_option( 'edac_site_id', 'site-1' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$request = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats' );

		$this->assertTrue( ( new REST_Api() )->can_get_scans_stats( $request ) );
		$this->assertFalse( get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
	}

	/**
	 * Run the private site registration handler.
	 *
	 * @return bool
	 */
	private function run_site_registration(): bool {
		$method = new ReflectionMethod( Connector::class, 'handle_site_registration' );
		$method->setAccessible( true );

		return $method->invoke( new Connector() );
	}

	/**
	 * Tests that a local site is not sent to My Dot for registration.
	 */
	public function test_registration_refused_on_local_site() {
		add_filter( 'edac_is_local_site', '__return_true' );
		update_option( 'edacp_license_key', 'test-key' );

		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return new WP_Error( 'http_request_failed', 'Should not run' );
			}
		);

		$this->assertFalse( $this->run_site_registration() );
		$this->assertSame( 0, $requests );
		$this->assertSame( '', (string) get_option( 'edac_site_id', '' ) );
	}

	/**
	 * Tests that a successful registration starts the missed pull clock.
	 */
	public function test_registration_records_baseline_activity() {
		add_filter( 'edac_is_local_site', '__return_false' );
		update_option( 'edacp_license_key', 'test-key' );
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) {
				if ( false !== strpos( (string) $url, 'register-site' ) ) {
					return [
						'headers'  => [],
						'body'     => wp_json_encode(
							[
								'success' => true,
								'data'    => [
									'site_id'         => 'site-2',
									'collection_interval_days' => 7,
									'next_collection' => gmdate( 'Y-m-d', time() + WEEK_IN_SECONDS ),
								],
							]
						),
						'response' => [
							'code'    => 200,
							'message' => 'OK',
						],
						'cookies'  => [],
					];
				}
				return new WP_Error( 'http_request_failed', 'Unexpected request' );
			},
			10,
			3
		);

		$this->assertTrue( $this->run_site_registration() );
		$this->assertSame( 'site-2', (string) get_option( 'edac_site_id', '' ) );
		$this->assertGreaterThan( 0, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
	}

	/**
	 * Tests that a local site does not use up a license activation.
	 */
	public function test_license_activation_refused_on_local_site() {
		add_filter( 'edac_is_local_site', '__return_true' );
		update_option( 'edacp_license_key', 'test-key' );

		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return new WP_Error( 'http_request_failed', 'Should not run' );
			}
		);

		$method = new ReflectionMethod( Connector::class, 'activate_license' );
		$method->setAccessible( true );
		$method->invoke( new Connector() );

		$this->assertSame( 0, $requests );
		$this->assertSame( '', (string) get_option( 'edac_license_status', '' ) );
	}

	/**
	 * Tests that a Pro activation on a local copy of a connected site does not register the local URL.
	 */
	public function test_pro_activation_does_not_register_local_copy() {
		add_filter( 'edac_is_local_site', '__return_true' );
		update_option( 'edac_site_id', 'site-1' );
		update_option( 'edacp_license_key', 'test-key' );

		$requests = 0;
		add_filter(
			'pre_http_request',
			function () use ( &$requests ) {
				++$requests;
				return new WP_Error( 'http_request_failed', 'Should not run' );
			}
		);

		( new Connector() )->handle_pro_license_activation();

		$this->assertSame( 0, $requests );
	}

	/**
	 * Render the reports page.
	 *
	 * @return string
	 */
	private function render_reports_page(): string {
		ob_start();
		( new AccessibilityReportsPage( 'manage_options' ) )->render_page();

		return (string) ob_get_clean();
	}

	/**
	 * Mark the site as connected for email reports.
	 */
	private function connect_site(): void {
		update_option( 'edac_license_status', 'valid' );
		update_option( 'edac_site_id', 'site-1' );
	}

	/**
	 * Tests that a healthy connected site still shows the enabled card.
	 */
	public function test_page_shows_enabled_when_no_issue() {
		$this->mock_http( 200 );
		$this->connect_site();

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'Reports Enabled', $output );
		$this->assertStringNotContainsString( 'Reports May Not Be Working', $output );
	}

	/**
	 * Tests that a missed pull replaces the enabled card with a warning.
	 */
	public function test_page_shows_warning_when_pull_is_overdue() {
		$this->mock_http( 200 );
		$this->connect_site();
		update_option( ConnectionStatus::LAST_ACTIVITY_OPTION, time() - 30 * DAY_IN_SECONDS );

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'Reports May Not Be Working', $output );
		$this->assertStringNotContainsString( 'Reports Enabled', $output );
	}

	/**
	 * Tests that a connected local site can still disable reports.
	 */
	public function test_connected_local_site_can_disable_reports() {
		add_filter( 'edac_is_local_site', '__return_true' );
		$this->connect_site();

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'Reports Cannot Run', $output );
		$this->assertStringContainsString( 'edac_jwt_unregister', $output );
	}

	/**
	 * Tests that a local site gets a notice instead of the enable form.
	 */
	public function test_page_hides_enable_form_on_local_site() {
		add_filter( 'edac_is_local_site', '__return_true' );

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'not available on local sites', $output );
		$this->assertStringNotContainsString( 'Enable Email Reports', $output );
	}

	/**
	 * Tests that the expired license card also explains a local site.
	 */
	public function test_expired_license_card_shows_local_notice() {
		add_filter( 'edac_is_local_site', '__return_true' );
		update_option( 'edac_site_id', 'test-site' );
		update_option( 'edac_license_status', 'expired' );

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'License Expired', $output );
		$this->assertStringContainsString( 'not available on local sites', $output );
	}

	/**
	 * Tests that a site behind htpasswd sees the notice and can still enable reports.
	 */
	public function test_page_shows_basic_auth_notice_and_can_still_enable_reports() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'password protected', $output );
		$this->assertStringContainsString( '/wp-json/accessibility-checker/v1/scans-stats', $output );
		$this->assertStringNotContainsString( 'upgrade to Pro', $output );
		$this->assertStringNotContainsString( 'your account', $output );
		$this->assertStringContainsString( 'Enable Email Reports', $output );
	}
}
