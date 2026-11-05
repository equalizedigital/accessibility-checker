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
	 * Tests that the first check starts the clock and shows no warning.
	 */
	public function test_first_check_sets_baseline_and_is_not_overdue() {
		$this->assertFalse( ConnectionStatus::is_pull_overdue() );
		$this->assertGreaterThan( 0, (int) get_option( ConnectionStatus::LAST_ACTIVITY_OPTION ) );
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
	 * Tests that a protected site with working credentials in My Dot shows no issue.
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
	 * Tests that a free site behind htpasswd sees the notice and can still enable reports.
	 */
	public function test_page_shows_upgrade_path_for_free_site_behind_basic_auth() {
		$this->mock_http( 401, [ 'www-authenticate' => 'Basic realm="Staging"' ] );

		$output = $this->render_reports_page();

		$this->assertStringContainsString( 'password protected', $output );
		$this->assertStringContainsString( 'upgrade to Pro', $output );
		$this->assertStringContainsString( 'Enable Email Reports', $output );
	}
}
