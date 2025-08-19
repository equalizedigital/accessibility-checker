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
	protected function setUp(): void {
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
	protected function tearDown(): void {
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

		self::$admin_id   = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$limited_id = $factory->user->create( [ 'role' => 'subscriber' ] );
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
    // Note: This project uses PHPUnit with the WordPress core test suite (WP_UnitTestCase).
    /**
     * Ensure unauthenticated users cannot submit scan results.
     */
    public function test_rest_post_scan_results_requires_authentication() {
        $this->assertNotNull( $this->server );
        wp_set_current_user( 0 );
        $violations = [
            [
                "ruleId" => "image-alt",
                "html"   => "<img src=\"/wp-includes/images/media/default.png\">",
                "impact" => "error",
                "landmark" => null,
            ],
        ];
        $request = new WP_REST_Request( "POST", "/accessibility-checker/v1/post-scan-results/" . self::$post_id );
        $request->set_param( "id", self::$post_id );
        $request->set_param( "violations", $violations );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 401, $response->get_status(), "Unauthenticated users should not be able to save scan results." );
    }
    /**
     * Ensure unauthenticated users cannot clear issues.
     */
    public function test_rest_clear_issues_requires_authentication() {
        $this->assertNotNull( $this->server );
        wp_set_current_user( 0 );
        $request = new WP_REST_Request( "POST", "/accessibility-checker/v1/clear-issues/" . self::$post_id );
        $request->set_param( "id", self::$post_id );
        $request->set_body( wp_json_encode( [ "flush" => true ] ) );
        $request->set_header( "Content-Type", "application/json" );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 401, $response->get_status(), "Unauthenticated users should not be able to clear issues." );
    }
    /**
     * Validate that non-array violations are rejected.
     */
    public function test_rest_post_scan_results_rejects_non_array_violations() {
        wp_set_current_user( self::$admin_id );
        $request = new WP_REST_Request( "POST", "/accessibility-checker/v1/post-scan-results/" . self::$post_id );
        $request->set_param( "id", self::$post_id );
        $request->set_param( "violations", "this-is-not-an-array" );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 400, $response->get_status(), "Invalid \"violations\" type should produce 400." );
    }
    /**
     * Validate that various violation payload shapes are accepted.
     */
    public function test_rest_post_scan_results_accepts_varied_violation_payloads() {
        wp_set_current_user( self::$admin_id );
        $violations = [
            [
                "ruleId"   => "image-alt",
                "html"     => "<img src=\"/wp-includes/images/media/default.png\">",
                "impact"   => "warning",
                "landmark" => "main",
                "extra"    => "ignored-field",
            ],
            [
                "ruleId"   => "color-contrast",
                "html"     => "<div style=\"color:#777;background:#777\">Text</div>",
                "impact"   => "critical",
                "landmark" => "",
            ],
            [
                "ruleId"   => "label",
                "html"     => "<input type=\"text\">",
                "impact"   => "minor",
                "landmark" => null,
            ],
        ];
        $request = new WP_REST_Request( "POST", "/accessibility-checker/v1/post-scan-results/" . self::$post_id );
        $request->set_param( "id", self::$post_id );
        $request->set_param( "violations", $violations );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 200, $response->get_status(), "Admin should be allowed to save varied scan results." );
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( "success", $data );
        $this->assertTrue( $data["success"] );
        $this->assertArrayHasKey( "id", $data );
        $this->assertSame( self::$post_id, $data["id"] );
    }
    /**
     * Validate flush=false behavior when clearing issues.
     */
    public function test_rest_clear_issues_flush_false_indicates_not_flushed() {
        wp_set_current_user( self::$admin_id );
        $request = new WP_REST_Request( "POST", "/accessibility-checker/v1/clear-issues/" . self::$post_id );
        $request->set_param( "id", self::$post_id );
        $request->set_body( wp_json_encode( [ "flush" => false ] ) );
        $request->set_header( "Content-Type", "application/json" );
        $response = $this->server->dispatch( $request );
        $this->assertSame( 200, $response->get_status(), "Admin should be allowed to clear issues with flush=false." );
        $data = $response->get_data();
        $this->assertIsArray( $data );
        $this->assertArrayHasKey( "success", $data );
        $this->assertTrue( $data["success"] );
        $this->assertArrayHasKey( "id", $data );
        $this->assertSame( self::$post_id, $data["id"] );
        $this->assertArrayHasKey( "flushed", $data );
        $this->assertFalse( $data["flushed"], "When flush=false, \"flushed\" should be false." );
    }
}
