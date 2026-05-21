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
		// Give limited user edit_posts but not edit_others_posts so they cannot edit this post.
		$user = new WP_User( self::$limited_id );
		$user->add_cap( 'edit_posts' );

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
		$own_post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
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
		$this->assertSame( 1, $data['ignre'] );
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
	 * Test: Single issue dismissed by unauthorized user fails with 403.
	 *
	 * Verifies that a user without edit_post capability for the post
	 * receives a 403 Forbidden response when attempting to dismiss an issue.
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

		// Set limited user (who cannot edit admin's post).
		wp_set_current_user( self::$limited_id );

		// Make the dismiss request.
		$request = new \WP_REST_Request( 'POST', '/accessibility-checker/v1/dismiss-issue/' . $issue_id );
		$request->set_param( 'action', 'dismiss' );

		$response = $this->server->dispatch( $request );

		// Verify response is 403 Forbidden.
		$this->assertSame( 403, $response->get_status(), 'Single issue dismiss by unauthorized user should return 403.' );
	}

	/**
	 * Test: Large batch dismissed by user with edit permission on all posts succeeds.
	 *
	 * Verifies that when a user has edit_post capability for all posts
	 * in a large batch, the endpoint dismisses all issues with one bulk
	 * UPDATE query and returns success.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_authorized_on_all() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Create posts for batch test (limited user owns all).
		$post_1 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Batch Post 1',
				'post_content' => 'Batch Content 1',
			]
		);

		$post_2 = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$limited_id,
				'post_title'   => 'Batch Post 2',
				'post_content' => 'Batch Content 2',
			]
		);

		$table_name   = $wpdb->prefix . 'accessibility_checker';
		$site_id      = get_current_blog_id();
		$batch_object = 'batch-all-authorized-test-' . wp_generate_uuid4();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		// Create multiple issues with the same object (batch).
		$issue_ids = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$post_id = ( $i <= 2 ) ? $post_1 : $post_2;
			$wpdb->insert(
				$table_name,
				[
					'postid'       => $post_id,
					'siteid'       => $site_id,
					'type'         => 'error',
					'rule'         => 'batch-auth-test-' . $i,
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
	 * Test: Large batch dismissed by user with partial authorization fails before bulk query.
	 *
	 * Verifies that when a user can edit only SOME posts in a large batch,
	 * the endpoint returns rest_forbidden BEFORE executing the bulk UPDATE query,
	 * ensuring no data is modified when permission checks fail.
	 *
	 * @return void
	 */
	public function test_large_batch_dismiss_authorized_on_some() {
		global $wpdb;

		$this->assertNotNull( $this->server );

		// Create posts: one owned by limited_id, one by admin_id.
		$limited_post = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
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
		// Create first issue on limited_id's post (limited user CAN edit).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $limited_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-partial-1',
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

		// Create second issue on admin_id's post (limited user CANNOT edit).
		$wpdb->insert(
			$table_name,
			[
				'postid'       => $admin_post,
				'siteid'       => $site_id,
				'type'         => 'error',
				'rule'         => 'batch-partial-2',
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

		// Verify response is 403 Forbidden.
		$this->assertSame( 403, $response->get_status(), 'Large batch dismiss with partial authorization should return 403.' );

		// Verify NO issues were updated (permission check failed before bulk query).
		$updated_issues = $wpdb->get_results(
			$wpdb->prepare( 'SELECT id, ignre FROM %i WHERE object = %s', $table_name, $batch_object ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $updated_issues as $issue ) {
			$this->assertSame( '0', $issue['ignre'], 'No issues should be updated when permission check fails.' );
		}
	}

	/**
	 * Test: Large batch dismissed by user with no authorization fails.
	 *
	 * Verifies that when a user cannot edit ANY posts in a large batch,
	 * the endpoint returns rest_forbidden immediately and no data is modified.
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
