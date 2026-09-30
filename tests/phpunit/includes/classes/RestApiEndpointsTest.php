<?php
/**
 * REST API endpoints behavior tests.
 *
 * @package Accessibility_Checker
 */

/**
 * Test class for REST API endpoints.
 *
 * @group rest
 */
class RestApiEndpointsTest extends WP_UnitTestCase {
	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Limited user ID.
	 *
	 * @var int
	 */
	protected static $limited_id;

	/**
	 * Subscriber user ID (no edit_posts capability).
	 *
	 * @var int
	 */
	protected static $subscriber_id;

	/**
	 * Post ID used for tests.
	 *
	 * @var int
	 */
	protected static $post_id;

	/**
	 * REST server instance for dispatching requests.
	 *
	 * @var WP_REST_Server|null
	 */
	private $server;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		// Initialize REST routes for each test.
		do_action( 'init' );
		do_action( 'rest_api_init' );
		$this->server = rest_get_server();
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Reset current user between tests.
		wp_set_current_user( 0 );
		// add_cap() writes directly to the user's wp_capabilities meta, which
		// WP_UnitTestCase's role restoration does not undo - several tests grant
		// this to the shared self::$limited_id fixture and never revoke it, which
		// would otherwise leak across every test that runs afterward.
		( new WP_User( self::$limited_id ) )->remove_cap( 'edac_dismiss_issues_globally' );
		parent::tearDown();
	}

	/**
	 * Create shared fixtures for this test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		// Ensure posts are scannable by plugin.
		update_option( 'edac_post_types', [ 'post' ] );

		// Ensure plugin DB table exists for tests (normally created via admin_init).
		( new \EDAC\Admin\Update_Database() )->edac_update_database();

		self::$admin_id      = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$limited_id    = $factory->user->create( [ 'role' => 'subscriber' ] );
		self::$subscriber_id = $factory->user->create( [ 'role' => 'subscriber' ] );
		// Give limited user edit_posts but not edit_others_posts so they cannot
		// edit other authors' posts - used to prove dismiss requires edit_post on
		// the target post (see test_single_issue_dismiss_unauthorized_user), so an
		// author can only dismiss issues on their own posts.
		$user = new WP_User( self::$limited_id );
		$user->add_cap( 'edit_posts' );
		// Grant edac_dismiss_own_issues; the "no capability" negative case is covered
		// separately in IgnoreCapabilityTest and
		// test_single_issue_dismiss_forbidden_without_ignore_capability.
		$user->add_cap( 'edac_dismiss_own_issues' );
		// Deliberately NOT edac_dismiss_issues_globally here - it's granted
		// per-test below where a largeBatch test specifically needs it, since
		// holding it now bypasses the per-post edit_post loop entirely
		// (see dismiss_issue()'s $can_ignore_globally short-circuit).

		self::$post_id = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'EDAC PHPUnit Post',
				'post_content' => '<main><h1>Title</h1><p>Img without alt <img src="/wp-includes/images/media/default.png"></p></main>',
			]
		);
	}

	/**
	 * Verify permissions for saving post scan results.
	 *
	 * @return void
	 */
	public function test_rest_post_scan_results_permissions() {
		$this->assertNotNull( $this->server );

		// Minimal violations payload similar to scanner output.
		$violations = [
			[
				'ruleId'   => 'image-alt',
				'html'     => '<img src="/wp-includes/images/media/default.png">',
				'impact'   => 'error',
				'landmark' => null,
			],
		];

		// Admin can POST results for the post.
		wp_set_current_user( self::$admin_id );
		$request = new WP_REST_Request( 'POST', '/accessibility-checker/v1/post-scan-results/' . self::$post_id );
		$request->set_param( 'id', self::$post_id );
		$request->set_param( 'violations', $violations );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Admin should be allowed to save scan results.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertSame( self::$post_id, $data['id'] );

		// Limited user cannot POST results for the admin-owned post.
		wp_set_current_user( self::$limited_id );
		$request2 = new WP_REST_Request( 'POST', '/accessibility-checker/v1/post-scan-results/' . self::$post_id );
		$request2->set_param( 'id', self::$post_id );
		$request2->set_param( 'violations', $violations );
		$response2 = $this->server->dispatch( $request2 );
		$this->assertSame( 403, $response2->get_status(), 'Limited user must not be allowed to save scan results for another user\'s post.' );
	}

	/**
	 * Verify permissions for clearing issues for a post.
	 *
	 * @return void
	 */
	public function test_rest_clear_issues_permissions() {
		$this->assertNotNull( $this->server );

		// Admin can clear issues.
		wp_set_current_user( self::$admin_id );
		$r1 = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-issues/' . self::$post_id );
		$r1->set_param( 'id', self::$post_id );
		$r1->set_body( wp_json_encode( [ 'flush' => true ] ) );
		$r1->set_header( 'Content-Type', 'application/json' );
		$resp1 = $this->server->dispatch( $r1 );
		$this->assertSame( 200, $resp1->get_status(), 'Admin should be allowed to clear issues.' );
		$body1 = $resp1->get_data();
		$this->assertIsArray( $body1 );
		$this->assertArrayHasKey( 'success', $body1 );
		$this->assertTrue( $body1['success'] );
		$this->assertArrayHasKey( 'id', $body1 );
		$this->assertSame( self::$post_id, $body1['id'] );
		$this->assertArrayHasKey( 'flushed', $body1 );
		$this->assertTrue( $body1['flushed'] );

		// Limited user cannot clear issues for a post they cannot edit.
		wp_set_current_user( self::$limited_id );
		$r2 = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-issues/' . self::$post_id );
		$r2->set_param( 'id', self::$post_id );
		$r2->set_body( wp_json_encode( [ 'flush' => true ] ) );
		$r2->set_header( 'Content-Type', 'application/json' );
		$resp2 = $this->server->dispatch( $r2 );
		$this->assertSame( 403, $resp2->get_status(), 'Limited user must not be allowed to clear issues.' );
	}

	/**
	 * Verify that a limited user can manage their own post.
	 *
	 * @return void
	 */
	public function test_limited_user_can_manage_own_post() {
		wp_set_current_user( self::$limited_id );
		$own_post_id = self::factory()->post->create(
			[
				'post_type'   => 'post',
				'post_status' => 'draft',
				'post_author' => self::$limited_id,
			]
		);

		// Save scan results.
		$req1 = new WP_REST_Request( 'POST', '/accessibility-checker/v1/post-scan-results/' . $own_post_id );
		$req1->set_param( 'id', $own_post_id );
		$req1->set_param(
			'violations',
			[
				[
					'ruleId' => 'image-alt',
					'html'   => '<img>',
				],
			]
		);
		$resp1 = $this->server->dispatch( $req1 );
		$this->assertSame( 200, $resp1->get_status() );
		$data1 = $resp1->get_data();
		$this->assertIsArray( $data1 );
		$this->assertArrayHasKey( 'success', $data1 );
		$this->assertTrue( $data1['success'] );

		// Clear issues.
		$req2 = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-issues/' . $own_post_id );
		$req2->set_param( 'id', $own_post_id );
		$req2->set_body( wp_json_encode( [ 'flush' => true ] ) );
		$req2->set_header( 'Content-Type', 'application/json' );
		$resp2 = $this->server->dispatch( $req2 );
		$this->assertSame( 200, $resp2->get_status() );
		$data2 = $resp2->get_data();
		$this->assertIsArray( $data2 );
		$this->assertArrayHasKey( 'success', $data2 );
		$this->assertTrue( $data2['success'] );
	}

	/**
	 * Verify permissions and payload shape for scans stats endpoint.
	 *
	 * @return void
	 */
	public function test_scans_stats_permissions_and_payload() {
		$this->assertNotNull( $this->server );

		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Admin should be allowed to access scans stats.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'stats', $data );
		// Verify stats structure is an array and includes a stable summary metric key.
		$this->assertIsArray( $data['stats'] );
		if ( ! empty( $data['stats'] ) ) {
			$this->assertArrayHasKey( 'scannable_posts_count', $data['stats'] );
		}

		wp_set_current_user( self::$subscriber_id );
		$request2  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats' );
		$response2 = $this->server->dispatch( $request2 );
		$this->assertSame( 403, $response2->get_status(), 'Subscriber without edit_posts should be denied scans stats access.' );
	}

	/**
	 * Verify permissions and payload shape for clear cached scans stats endpoint.
	 *
	 * @return void
	 */
	public function test_clear_cached_scans_stats_permissions_and_payload() {
		$this->assertNotNull( $this->server );

		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-cached-scans-stats' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Admin should be allowed to clear cached scans stats.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );

		wp_set_current_user( self::$subscriber_id );
		$request2  = new WP_REST_Request( 'POST', '/accessibility-checker/v1/clear-cached-scans-stats' );
		$response2 = $this->server->dispatch( $request2 );
		$this->assertSame( 403, $response2->get_status(), 'Subscriber without publish_posts should be denied cache clear.' );
	}

	/**
	 * Verify scans stats by post type endpoint handles allowed and disallowed post types.
	 *
	 * @return void
	 */
	public function test_scans_stats_by_post_type_status_codes() {
		$this->assertNotNull( $this->server );

		wp_set_current_user( self::$admin_id );

		$disallowed_request  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats-by-post-type/page' );
		$disallowed_response = $this->server->dispatch( $disallowed_request );
		$this->assertSame( 400, $disallowed_response->get_status(), 'Non-scannable post type should return 400.' );
		$disallowed_data = $disallowed_response->get_data();
		$this->assertIsArray( $disallowed_data );
		$this->assertArrayHasKey( 'message', $disallowed_data );

		$allowed_request  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats-by-post-type/post' );
		$allowed_response = $this->server->dispatch( $allowed_request );
		$this->assertSame( 200, $allowed_response->get_status(), 'Scannable post type should return 200.' );
		$allowed_data = $allowed_response->get_data();
		$this->assertIsArray( $allowed_data );
		$this->assertArrayHasKey( 'success', $allowed_data );
		$this->assertTrue( $allowed_data['success'] );
		$this->assertArrayHasKey( 'stats', $allowed_data );
	}

	/**
	 * Verify scans stats by post types endpoint permissions and payload shape.
	 *
	 * @return void
	 */
	public function test_scans_stats_by_post_types_permissions_and_payload() {
		$this->assertNotNull( $this->server );

		wp_set_current_user( self::$admin_id );
		$request  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats-by-post-types' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'Admin should be allowed to access scans stats by post types.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'stats', $data );
		// Verify stats structure is a keyed map where each key is a post type slug.
		$this->assertIsArray( $data['stats'] );
		if ( ! empty( $data['stats'] ) ) {
			foreach ( $data['stats'] as $post_type => $stat ) {
				$this->assertIsString( $post_type );
				$this->assertNotSame( '', $post_type );
				// Each value is either false (non-scannable) or a summary array (scannable).
				$this->assertTrue( false === $stat || is_array( $stat ) );
			}
		}

		wp_set_current_user( self::$subscriber_id );
		$request2  = new WP_REST_Request( 'GET', '/accessibility-checker/v1/scans-stats-by-post-types' );
		$response2 = $this->server->dispatch( $request2 );
		$this->assertSame( 403, $response2->get_status(), 'Subscriber without edit_posts should be denied scans stats by post types.' );
	}

	/**
	 * Dismiss issue test data: post ID to object mapping.
	 *
	 * @var array
	 */
	protected static $dismiss_test_posts = [];

	/**
	 * Dismiss issue test data: issue IDs created for batch testing.
	 *
	 * @var array
	 */
	protected static $dismiss_test_issues = [];

	/**
	 * Set up dismiss-issue test fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass_DismissIssues( $factory ) {
		// Create posts for dismiss tests.
		self::$dismiss_test_posts['admin_post_1']   = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin Post 1',
				'post_content' => 'Content 1',
			]
		);
		self::$dismiss_test_posts['admin_post_2']   = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin Post 2',
				'post_content' => 'Content 2',
			]
		);
		self::$dismiss_test_posts['limited_post_1'] = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Limited Post 1',
				'post_content' => 'Content Limited 1',
			]
		);
		self::$dismiss_test_posts['limited_post_2'] = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Limited Post 2',
				'post_content' => 'Content Limited 2',
			]
		);

		// Create test issues in the accessibility_checker table.
		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB calls required in tests.
		// Single issue for testing single dismiss by authorized user.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => self::$dismiss_test_posts['admin_post_1'],
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'test-rule-1',
				'ruletype'     => 'error',
				'object'       => 'single-issue-test-1',
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		self::$dismiss_test_issues['single_auth'] = $wpdb->insert_id;

		// Batch of issues for testing batch dismiss with all authorized.
		$batch_object = 'batch-issue-test-all-auth';
		for ( $i = 1; $i <= 3; $i++ ) {
			$wpdb->insert(
				$table_name,
				[
					'postid'       => self::$dismiss_test_posts['limited_post_1'],
					'siteid'       => $site_id,
					'type'         => 'error',
					'rule'         => 'test-rule-batch-' . $i,
					'ruletype'     => 'error',
					'object'       => $batch_object,
					'recordcheck'  => 1,
					'user'         => self::$limited_id,
					'ignre'        => 0,
					'ignre_global' => 0,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
			);
			self::$dismiss_test_issues[ "batch_all_auth_$i" ] = $wpdb->insert_id;
		}

		// Batch of issues for testing batch dismiss with partial authorization.
		$batch_object_partial = 'batch-issue-test-partial-auth';
		// First issue: limited user can edit their own post.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => self::$dismiss_test_posts['limited_post_1'],
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'test-rule-partial-1',
				'ruletype'     => 'error',
				'object'       => $batch_object_partial,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		self::$dismiss_test_issues['batch_partial_auth_1'] = $wpdb->insert_id;

		// Second issue: same batch but different post (admin's post - limited user cannot edit).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => self::$dismiss_test_posts['admin_post_1'],
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'test-rule-partial-2',
				'ruletype'     => 'error',
				'object'       => $batch_object_partial,
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		self::$dismiss_test_issues['batch_partial_auth_2'] = $wpdb->insert_id;

		// Batch of issues for testing batch dismiss with no authorization.
		$batch_object_no_auth = 'batch-issue-test-no-auth';
		for ( $i = 1; $i <= 3; $i++ ) {
			$wpdb->insert(
				$table_name,
				[
					'postid'       => self::$dismiss_test_posts['admin_post_1'],
					'siteid'       => $site_id,
					'type'         => 'error',
					'rule'         => 'test-rule-no-auth-' . $i,
					'ruletype'     => 'error',
					'object'       => $batch_object_no_auth,
					'recordcheck'  => 1,
					'user'         => self::$admin_id,
					'ignre'        => 0,
					'ignre_global' => 0,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
			);
			self::$dismiss_test_issues[ "batch_no_auth_$i" ] = $wpdb->insert_id;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Test: Single issue dismissed by authorized user succeeds.
	 *
	 * Verifies that an authorized user can dismiss a single issue and
	 * the response contains correct data.
	 *
	 * @return void
	 */
	public function test_single_issue_dismiss_authorized_user() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Set limited user (who can edit their own posts).
		wp_set_current_user( self::$limited_id );

		// Create a test issue on the limited user's own post.
		// Use 'draft' so the limited user (who only has edit_posts, not edit_published_posts)
		// can edit it — WordPress requires edit_published_posts for published posts.
		$own_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Test Single Issue Post',
				'post_content' => 'Test content',
			]
		);

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $own_post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'single-auth-test',
				'ruletype'     => 'error',
				'object'       => 'single-authorized-test',
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);

		$issue_id = $wpdb->insert_id;

		// Make the dismiss request.
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'reason', 'Intentional' );
		$request->set_param( 'comment', 'This is intentional' );
		$request->set_param( 'ignore_global', 0 );

		$response = $this->server->dispatch( $request );

		// Verify response is successful.
		$this->assertSame( 200, $response->get_status(), 'Single issue dismiss by authorized user should return 200.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertSame( $issue_id, $data['issue_id'] );
		$this->assertSame( 'dismiss', $data['action'] );
		$this->assertTrue( $data['ignre'] ); // endpoint returns bool $is_ignoring.
		$this->assertSame( self::$limited_id, $data['ignre_user'] );

		// Verify database was updated.
		$updated_issue = $wpdb->get_row(
			$wpdb->prepare( 'SELECT ignre, ignre_reason, ignre_comment FROM %i WHERE id = %d', $table_name, $issue_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assertSame( '1', $updated_issue['ignre'] );
		$this->assertSame( 'Intentional', $updated_issue['ignre_reason'] );
		$this->assertSame( 'This is intentional', $updated_issue['ignre_comment'] );
	}

	/**
	 * Test: A user who can edit_post but lacks the edac_dismiss_own_issues
	 * capability is forbidden from dismissing, even though edit_post alone
	 * used to be sufficient.
	 *
	 * @return void
	 */
	public function test_single_issue_dismiss_forbidden_without_ignore_capability() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// User can edit their own post, but was never granted edac_dismiss_own_issues.
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		( new WP_User( $user_id ) )->add_cap( 'edit_posts' );
		wp_set_current_user( $user_id );

		$own_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => $user_id,
				'post_title'   => 'Test Ignore Capability Post',
				'post_content' => 'Test content',
			]
		);

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $own_post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'single-no-ignore-cap-test',
				'ruletype'     => 'error',
				'object'       => 'single-no-ignore-cap-test',
				'recordcheck'  => 1,
				'user'         => $user_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$issue_id = $wpdb->insert_id;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'A user without edac_dismiss_own_issues should not be able to dismiss even their own editable post.' );
	}

	/**
	 * Test: Single issue dismiss on a post the user cannot edit fails with 403.
	 *
	 * The dismiss capability does NOT override core edit_post: a user holding
	 * edac_dismiss_own_issues but WITHOUT edit_post on the target post (here the
	 * limited user dismissing an issue on the admin's post) is refused. Authors
	 * can therefore only dismiss issues on their own editable posts.
	 *
	 * @return void
	 */
	public function test_single_issue_dismiss_unauthorized_user() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Create issue on admin's post.
		$admin_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Test Admin Post Unauthorized',
				'post_content' => 'Test content',
			]
		);

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'single-unauth-test',
				'ruletype'     => 'error',
				'object'       => 'single-unauthorized-test',
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);

		$issue_id = $wpdb->insert_id;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Set limited user (holds edac_dismiss_own_issues but cannot edit admin's post).
		wp_set_current_user( self::$limited_id );

		// Make the dismiss request.
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );

		$response = $this->server->dispatch( $request );

		// Verify response is 403 Forbidden - the capability does not override edit_post.
		$this->assertSame( 403, $response->get_status(), 'Single issue dismiss on a post the user cannot edit should return 403.' );
	}

	/**
	 * Test: edac_dismiss_issues (any post) lets a holder dismiss on a post they
	 * cannot edit.
	 *
	 * The limited user is granted the site-wide dismiss capability and dismisses
	 * an issue on the admin's post (which they cannot edit_post). This is the
	 * deliberate, opt-in superset of edac_dismiss_own_issues.
	 *
	 * @return void
	 */
	public function test_single_issue_dismiss_any_post_capability_allows_foreign_post() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		$admin_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Test Admin Post Any-Dismiss',
				'post_content' => 'Test content',
			]
		);

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'single-any-test',
				'ruletype'     => 'error',
				'object'       => 'single-any-test',
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);

		$issue_id = $wpdb->insert_id;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Grant the site-wide dismiss capability to the limited user for this test.
		$any_user = new WP_User( self::$limited_id );
		$any_user->add_cap( 'edac_dismiss_issues' );

		wp_set_current_user( self::$limited_id );

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );

		$response = $this->server->dispatch( $request );

		$any_user->remove_cap( 'edac_dismiss_issues' );

		$this->assertSame( 200, $response->get_status(), 'A holder of edac_dismiss_issues may dismiss an issue on a post they cannot edit.' );
	}

	/**
	 * Test: A single-post dismiss never stamps the ignre_global marker unless the
	 * user holds edac_dismiss_issues_globally.
	 *
	 * The limited user (edac_dismiss_own_issues, no global capability) dismisses an
	 * issue on their OWN editable post while requesting ignore_global=1; the row
	 * is dismissed but the global marker stays 0.
	 *
	 * @return void
	 */
	public function test_single_issue_dismiss_does_not_set_global_marker_without_capability() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Use 'draft' so the limited user (who only has edit_posts, not
		// edit_published_posts) can edit their own post.
		$own_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Limited Own Post Marker',
				'post_content' => 'Test content',
			]
		);

		$table_name = $wpdb->prefix . 'accessibility_checker';
		$site_id    = get_current_blog_id();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $own_post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'single-marker-test',
				'ruletype'     => 'error',
				'object'       => 'single-marker-test',
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);

		$issue_id = $wpdb->insert_id;

		wp_set_current_user( self::$limited_id );

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'ignore_global', 1 );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), 'The user may dismiss an issue on their own editable post.' );

		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT ignre, ignre_global FROM %i WHERE id = %d', $table_name, $issue_id ), ARRAY_A );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertSame( '1', (string) $row['ignre'], 'The issue should be dismissed.' );
		$this->assertSame( '0', (string) $row['ignre_global'], 'A user without the global capability must not set the ignre_global marker.' );
	}

	/**
	 * Test: Large batch dismissed by a user with edac_dismiss_issues_globally
	 * succeeds.
	 *
	 * Verifies that a user holding the global-ignore capability can dismiss
	 * an entire batch with one bulk UPDATE query and a 200 response.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_authorized_on_all() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		// Create posts for batch test (limited user owns all).
		// Use 'draft' so the limited user (who only has edit_posts, not edit_published_posts)
		// can edit them — WordPress requires edit_published_posts for published posts.
		$post_1 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Batch Post 1',
				'post_content' => 'Batch Content 1',
			]
		);

		$post_2 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Batch Post 2',
				'post_content' => 'Batch Content 2',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-all-authorized-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Create multiple issues sharing the same rule AND object (batch).
		$issue_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$post_id = ( $i <= 2 ) ? $post_1 : $post_2;
			$wpdb->insert(
				$table_name,
				[
					'postid'       => $post_id,
					'siteid'       => $site_id,
					'type'         => 'error',
					'rule'         => 'batch-auth-test',
					'ruletype'     => 'error',
					'object'       => $batch_object,
					'recordcheck'  => 1,
					'user'         => self::$limited_id,
					'ignre'        => 0,
					'ignre_global' => 0,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
			);
			$issue_ids[ $i ] = $wpdb->insert_id;
		}

		// Set limited user (who owns all posts).
		wp_set_current_user( self::$limited_id );

		// Make the dismiss request with largeBatch flag.
		$first_issue_id = $issue_ids[1];
		$request        = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $first_issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'reason', 'Batch intentional' );
		$request->set_param( 'largeBatch', true );
		$request->set_param( 'ignore_global', 0 );

		$response = $this->server->dispatch( $request );

		// Verify response is successful.
		$this->assertSame( 200, $response->get_status(), 'Large batch dismiss by fully authorized user should return 200.' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['large_batch'] );

		// Verify ALL issues in the batch were updated.
		$updated_issues = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre FROM %i WHERE object = %s ORDER BY id', $table_name, $batch_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertCount( 3, $updated_issues, 'All 3 issues in the batch should be updated.' );
		foreach ( $updated_issues as $issue ) {
			$this->assertSame( '1', $issue['ignre'], 'All issues in batch should have ignre = 1.' );
		}
	}

	/**
	 * Test: A user who can edit_post on every affected post, and has
	 * edac_dismiss_own_issues, but was never granted edac_dismiss_issues_globally,
	 * must still be forbidden from a largeBatch dismiss - per-post edit
	 * permission is not a substitute for the global-ignore capability, since
	 * largeBatch updates every row sharing the object regardless of which
	 * single issue_id the request named.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_forbidden_without_global_ignore_capability() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// User can edit their own post and has edac_dismiss_own_issues, but was
		// never granted edac_dismiss_issues_globally.
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$user    = new WP_User( $user_id );
		$user->add_cap( 'edit_posts' );
		$user->add_cap( 'edac_dismiss_own_issues' );

		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => $user_id,
				'post_title'   => 'No Global Ignore Batch Post',
				'post_content' => 'No Global Ignore Batch Content',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-no-global-cap-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_id,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-no-global-cap-test',
				'ruletype'     => 'error',
				'object'       => $batch_object,
				'recordcheck'  => 1,
				'user'         => $user_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$issue_id = $wpdb->insert_id;

		wp_set_current_user( $user_id );

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'largeBatch dismiss without edac_dismiss_issues_globally should return 403 even when the user can edit every affected post.' );

		$updated_issue = $wpdb->get_row(
			$wpdb->prepare( 'SELECT ignre FROM %i WHERE id = %d', $table_name, $issue_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertSame( '0', $updated_issue['ignre'], 'Issue should not have been dismissed.' );
	}

	/**
	 * Test: Large batch dismiss only affects rows sharing the same rule, not just the same object.
	 *
	 * Regression test for PRO-1264: a global/large-batch dismiss on an issue must only touch
	 * other rows with the same `rule` AND `object`. Rows that share only the `object` (a
	 * different rule against the same DOM node) must be left untouched.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_only_affects_matching_rule() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// largeBatch itself requires edac_dismiss_issues_globally now (PRO-1239) -
		// unrelated to the rule+object scoping this test targets, but a
		// prerequisite to reach the code path at all.
		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		$post_1 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Rule Scoping Post 1',
				'post_content' => 'Rule Scoping Content 1',
			]
		);

		$post_2 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Rule Scoping Post 2',
				'post_content' => 'Rule Scoping Content 2',
			]
		);

		$post_3 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Rule Scoping Post 3',
				'post_content' => 'Rule Scoping Content 3',
			]
		);

		$table_name    = $wpdb->prefix . 'accessibility_checker';
		$site_id       = get_current_blog_id();
		$shared_object = 'rule-scoping-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Post 1 and Post 2 share the same rule AND object — these should both be dismissed together.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_1,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'missing_alt_text',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$matching_issue_id_1 = $wpdb->insert_id;

		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_2,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'missing_alt_text',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$matching_issue_id_2 = $wpdb->insert_id;

		// Post 3 shares the same object but has a DIFFERENT rule — must NOT be dismissed.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_3,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'color_contrast_failure',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$unrelated_rule_issue_id = $wpdb->insert_id;

		// Set limited user (who owns all three posts).
		wp_set_current_user( self::$limited_id );

		// Dismiss globally, starting from one of the matching-rule issues.
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $matching_issue_id_1 );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'reason', 'Rule scoping test' );
		$request->set_param( 'largeBatch', true );
		$request->set_param( 'ignore_global', 1 );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), 'Large batch dismiss should succeed for the authorized user.' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for assertions.
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre, ignre_global FROM %i WHERE object = %s ORDER BY id', $table_name, $shared_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertCount( 3, $rows, 'All three seeded rows should still exist.' );

		$ignre_by_id        = array_column( $rows, 'ignre', 'id' );
		$ignre_global_by_id = array_column( $rows, 'ignre_global', 'id' );

		$this->assertSame( '1', $ignre_by_id[ $matching_issue_id_1 ], 'The dismissed issue itself should be marked ignored.' );
		$this->assertSame( '1', $ignre_by_id[ $matching_issue_id_2 ], 'The other row sharing rule+object should also be dismissed.' );
		$this->assertSame( '1', $ignre_global_by_id[ $matching_issue_id_1 ], 'The dismissed issue should be marked as a global ignore.' );
		$this->assertSame( '1', $ignre_global_by_id[ $matching_issue_id_2 ], 'The other row sharing rule+object should also be marked as a global ignore.' );
		$this->assertSame(
			'0',
			$ignre_by_id[ $unrelated_rule_issue_id ],
			'A row sharing only the object (different rule) must NOT be dismissed by a global/large-batch action.'
		);
	}

	/**
	 * Test: Large batch reopen (undismiss) only affects rows sharing the same rule, not just the same object.
	 *
	 * Mirrors test_large_batch_dismiss_only_affects_matching_rule for the reopen direction:
	 * a largeBatch action that is NOT a recognized ignore action (e.g. 'reopen') must only
	 * clear rows matching both rule and object, leaving a row that shares only the object
	 * (a different rule) still ignored.
	 *
	 * @return void
	 */
	public function test_large_batch_reopen_only_affects_matching_rule() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// largeBatch itself requires edac_dismiss_issues_globally now (PRO-1239).
		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		$post_1 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Reopen Scoping Post 1',
				'post_content' => 'Reopen Scoping Content 1',
			]
		);

		$post_2 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Reopen Scoping Post 2',
				'post_content' => 'Reopen Scoping Content 2',
			]
		);

		$post_3 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Reopen Scoping Post 3',
				'post_content' => 'Reopen Scoping Content 3',
			]
		);

		$table_name    = $wpdb->prefix . 'accessibility_checker';
		$site_id       = get_current_blog_id();
		$shared_object = 'reopen-scoping-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// All three rows start pre-ignored/globally-ignored. Post 1 and Post 2 share rule+object.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_1,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'missing_alt_text',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 1,
				'ignre_global' => 1,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$matching_issue_id_1 = $wpdb->insert_id;

		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_2,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'missing_alt_text',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 1,
				'ignre_global' => 1,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$matching_issue_id_2 = $wpdb->insert_id;

		// Post 3 shares the same object but has a DIFFERENT rule — must stay ignored.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $post_3,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'color_contrast_failure',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 1,
				'ignre_global' => 1,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$unrelated_rule_issue_id = $wpdb->insert_id;

		// Set limited user (who owns all three posts).
		wp_set_current_user( self::$limited_id );

		// Reopen globally, starting from one of the matching-rule issues. Any action string
		// outside the recognized ignore-actions list is treated as "reopen" by dismiss_issue().
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $matching_issue_id_1 );
		$request->set_param( 'action', 'undismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status(), 'Large batch reopen should succeed for the authorized user.' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for assertions.
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre, ignre_global FROM %i WHERE object = %s ORDER BY id', $table_name, $shared_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertCount( 3, $rows, 'All three seeded rows should still exist.' );

		$ignre_by_id = array_column( $rows, 'ignre', 'id' );

		$this->assertSame( '0', $ignre_by_id[ $matching_issue_id_1 ], 'The reopened issue itself should be marked open.' );
		$this->assertSame( '0', $ignre_by_id[ $matching_issue_id_2 ], 'The other row sharing rule+object should also be reopened.' );
		$this->assertSame(
			'1',
			$ignre_by_id[ $unrelated_rule_issue_id ],
			'A row sharing only the object (different rule) must remain ignored after a large-batch reopen.'
		);
	}

	/**
	 * Test: Large batch dismiss does not touch a row that shares only the object
	 * (different rule) on a post the actor can't individually edit, even though
	 * PRO-1239 makes edac_dismiss_issues_globally bypass the per-post edit_post
	 * loop entirely once granted.
	 *
	 * Before the PRO-1264 fix, the batch query matched on object alone, so this
	 * unrelated-rule row would have been silently touched too. After PRO-1239,
	 * this test can no longer prove "succeeds despite lacking edit_post on that
	 * post" (edac_dismiss_issues_globally is required just to reach this code
	 * path, and once granted it bypasses per-post checks for every row in
	 * scope regardless of rule) - but the rule filter excluding that row from
	 * the batch in the first place remains independently meaningful and is
	 * what this test now verifies.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_succeeds_when_unrelated_rule_row_is_on_unauthorized_post() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		$limited_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Scope Narrowing Limited Post',
				'post_content' => 'Scope Narrowing Limited Content',
			]
		);

		$admin_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Scope Narrowing Admin Post',
				'post_content' => 'Scope Narrowing Admin Content',
			]
		);

		$table_name    = $wpdb->prefix . 'accessibility_checker';
		$site_id       = get_current_blog_id();
		$shared_object = 'scope-narrowing-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Limited user's own issue: the rule being dismissed.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $limited_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'missing_alt_text',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$issue_id = $wpdb->insert_id;

		// Admin-owned post's issue: same object, DIFFERENT rule.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'color_contrast_failure',
				'ruletype'     => 'error',
				'object'       => $shared_object,
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$unrelated_rule_issue_id = $wpdb->insert_id;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		wp_set_current_user( self::$limited_id );

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'Large batch dismiss should succeed for the authorized user.'
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Need fresh data for assertions.
		$unrelated_ignre = $wpdb->get_var(
			$wpdb->prepare( 'SELECT ignre FROM %i WHERE id = %d', $table_name, $unrelated_rule_issue_id )
		);
		$this->assertSame(
			'0',
			$unrelated_ignre,
			'A row sharing only the object (different rule) must NOT be dismissed, even though edac_dismiss_issues_globally would otherwise bypass the per-post edit_post check for it.'
		);
	}

	/**
	 * Test: Large batch dismiss with edac_dismiss_issues_globally succeeds
	 * across posts the user cannot individually edit.
	 *
	 * Verifies that a user with edac_dismiss_issues_globally can dismiss a
	 * batch that includes a post they do NOT personally have edit_post on -
	 * the whole point of the capability is to bypass that per-post
	 * ownership check, not just to unlock largeBatch requests in general.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_bypasses_per_post_check_with_global_capability() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		// Create posts: one owned by limited_id, one by admin_id. limited_id
		// only has edit_posts (not edit_others_posts), so without the
		// global-ignore bypass they could edit_post on the first but not the
		// second - that's exactly the distinction this test proves no longer
		// matters once edac_dismiss_issues_globally is granted.
		$limited_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'draft',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Limited Batch Post',
				'post_content' => 'Limited Batch Content',
			]
		);

		$admin_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin Batch Post',
				'post_content' => 'Admin Batch Content',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-partial-authorized-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Create first issue on limited_id's own post.
		// Both rows share the same rule AND object so they land in the same batch.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $limited_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-partial-test',
				'ruletype'     => 'error',
				'object'       => $batch_object,
				'recordcheck'  => 1,
				'user'         => self::$limited_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		$first_issue_id = $wpdb->insert_id;

		// Create second issue on admin_id's post - limited_id has no
		// edit_post on this one, which is exactly what the global capability
		// should bypass.
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-partial-test',
				'ruletype'     => 'error',
				'object'       => $batch_object,
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);

		// Set limited user.
		wp_set_current_user( self::$limited_id );

		// Make the dismiss request with largeBatch flag.
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $first_issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		// Verify response is successful despite limited_id lacking edit_post
		// on the admin-owned post - the global capability is the gate now.
		$this->assertSame( 200, $response->get_status(), 'Large batch dismiss with edac_dismiss_issues_globally should succeed even across posts the user cannot individually edit.' );

		// Verify BOTH issues were updated, including the one on the post
		// limited_id doesn't own.
		$updated_issues = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre FROM %i WHERE object = %s', $table_name, $batch_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertCount( 2, $updated_issues, 'Both issues in the batch should be updated.' );
		foreach ( $updated_issues as $issue ) {
			$this->assertSame( '1', $issue['ignre'], 'Both issues should be dismissed, including the one on the post the user cannot individually edit.' );
		}

		// The route's own permission_callback does a separate edit_post lookup
		// against whichever post the URL's issue_id resolves to, before the
		// handler above (and its per-post bypass) ever runs - $first_issue_id
		// resolves to limited_id's own post, so this assertion alone can't
		// prove that lookup also respects the global capability. See
		// test_large_batch_dismiss_permission_callback_bypasses_edit_post_on_representative_post()
		// for the case where the URL's own post isn't one the user can edit.
	}

	/**
	 * Test: the dismiss-issue route's permission_callback must not require
	 * edit_post on the URL's own representative post when the request is a
	 * largeBatch global-ignore - that check runs before dismiss_issue() (and
	 * its per-post bypass) is ever reached, so gating it on ownership of one
	 * specific post would block exactly the requests edac_dismiss_issues_globally
	 * exists to allow, any time that one post isn't personally owned by the
	 * caller.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_permission_callback_bypasses_edit_post_on_representative_post() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		( new WP_User( self::$limited_id ) )->add_cap( 'edac_dismiss_issues_globally' );

		// Post owned by admin - limited_id has no edit_post on this one.
		$admin_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin-Only Representative Post',
				'post_content' => 'Admin-Only Representative Content',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-representative-post-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-representative-post',
				'ruletype'     => 'error',
				'object'       => $batch_object,
				'recordcheck'  => 1,
				'user'         => self::$admin_id,
				'ignre'        => 0,
				'ignre_global' => 0,
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
		);
		// The URL's issue_id resolves to $admin_post - the post limited_id
		// cannot edit - which is exactly what the permission_callback's own
		// edit_post lookup would otherwise gate on.
		$issue_id = $wpdb->insert_id;

		wp_set_current_user( self::$limited_id );

		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'largeBatch dismiss with edac_dismiss_issues_globally should not be blocked by the permission_callback\'s edit_post check on the URL\'s own representative post.'
		);

		$updated_issue = $wpdb->get_row(
			$wpdb->prepare( 'SELECT ignre FROM %i WHERE id = %d', $table_name, $issue_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->assertSame( '1', $updated_issue['ignre'], 'Issue should have been dismissed.' );
	}

	/**
	 * Test: Large batch dismissed by a user without edac_dismiss_issues_globally
	 * fails, even though every affected post happens to belong to someone
	 * else (a scenario that would also fail the per-post edit_post loop, if
	 * that loop were ever reached - it isn't here, since the capability gate
	 * runs first for every largeBatch request).
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_unauthorized_on_all() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Create posts all owned by admin_id.
		$admin_post_1 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin Batch Post 1',
				'post_content' => 'Admin Batch Content 1',
			]
		);

		$admin_post_2 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_title'   => 'Admin Batch Post 2',
				'post_content' => 'Admin Batch Content 2',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-no-authorized-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Create multiple issues all on admin's posts.
		$issue_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$post_id = ( $i <= 2 ) ? $admin_post_1 : $admin_post_2;
			$wpdb->insert(
				$table_name,
				[
					'postid'       => $post_id,
					'siteid'       => $site_id,
					'type'         => 'error',
					'rule'         => 'batch-no-auth-' . $i,
					'ruletype'     => 'error',
					'object'       => $batch_object,
					'recordcheck'  => 1,
					'user'         => self::$admin_id,
					'ignre'        => 0,
					'ignre_global' => 0,
				],
				[ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d' ]
			);
			$issue_ids[ $i ] = $wpdb->insert_id;
		}

		// Set limited user (who cannot edit admin's posts).
		wp_set_current_user( self::$limited_id );

		// Make the dismiss request with largeBatch flag.
		$first_issue_id = $issue_ids[1];
		$request        = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $first_issue_id );
		$request->set_param( 'action', 'dismiss' );
		$request->set_param( 'largeBatch', true );

		$response = $this->server->dispatch( $request );

		// Verify response is 403 Forbidden.
		$this->assertSame( 403, $response->get_status(), 'Large batch dismiss by completely unauthorized user should return 403.' );

		// Verify NO issues were updated.
		$updated_issues = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre FROM %i WHERE object = %s', $table_name, $batch_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $updated_issues as $issue ) {
			$this->assertSame( '0', $issue['ignre'], 'No issues should be updated when user lacks edit permission on all posts.' );
		}
	}
}
