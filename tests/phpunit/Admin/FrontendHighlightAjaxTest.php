<?php
/**
 * AJAX behavior tests for the frontend highlighter's data endpoint.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Frontend_Highlight;
use EDAC\Admin\Update_Database;

/**
 * Tests for Frontend_Highlight.
 *
 * Covers the request guards in ajax() (nonce, missing post id, missing post, password protected
 * post, a logged in user without read access, anonymous visitors), the rule filtering in
 * get_issues(), the hook wiring in init_hooks() and the issue payload the endpoint returns,
 * alongside the original PRO-1290 capability enforcement cases.
 */
class FrontendHighlightAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Handler under test.
	 *
	 * @var Frontend_Highlight
	 */
	private $frontend_highlight;

	/**
	 * Post ID used for tests.
	 *
	 * @var int
	 */
	protected static $post_id;

	/**
	 * Create shared fixtures for this test class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		// Ensure plugin DB table exists for tests.
		( new Update_Database() )->edac_update_database();

		self::$post_id = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_content' => '<p>Content for frontend highlighter AJAX tests.</p>',
			]
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name,
			[
				'postid'   => self::$post_id,
				'siteid'   => get_current_blog_id(),
				'rule'     => 'empty_paragraph_tag',
				'ruletype' => 'error',
				'object'   => 'test',
				'selector' => 'body',
			]
		);
	}

	/**
	 * Set up before each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->frontend_highlight = new Frontend_Highlight();
		add_action( 'wp_ajax_edac_frontend_highlight_ajax', [ $this->frontend_highlight, 'ajax' ] );
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_frontend_highlight_ajax', [ $this->frontend_highlight, 'ajax' ] );
		edac_ignore_capability()->sync_matrix( [] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Insert a row into the issues table for a post.
	 *
	 * @param int    $post_id   The post the issue is recorded against.
	 * @param string $rule      The rule slug stored on the row.
	 * @param array  $overrides Column values that replace the defaults.
	 * @return void
	 */
	private function insert_issue( int $post_id, string $rule, array $overrides = [] ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'accessibility_checker',
			array_merge(
				[
					'postid'   => $post_id,
					'siteid'   => get_current_blog_id(),
					'rule'     => $rule,
					'ruletype' => 'error',
					'object'   => 'test object',
					'selector' => 'body',
				],
				$overrides
			)
		);
	}

	/**
	 * Dispatch the frontend highlighter AJAX action and decode the captured response.
	 *
	 * The base class only replaces the captured response when the handler produced output, so a
	 * stale body has to be cleared before each dispatch or a silent handler would look like the
	 * previous test's response.
	 *
	 * @param array $request Request values to place in $_POST. A fresh nonce is always added.
	 * @return array The decoded response body.
	 */
	private function dispatch_highlighter_ajax( array $request ): array {
		$_POST = array_merge( [ 'nonce' => wp_create_nonce( 'frontend-highlighter' ) ], $request );

		$this->_last_response = '';

		try {
			$this->_handleAjax( 'edac_frontend_highlight_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		return (array) json_decode( $this->_last_response, true );
	}

	/**
	 * Assert that a request was rejected with a specific message.
	 *
	 * @param array  $response The decoded response body.
	 * @param string $message  The message the handler returns for the rejected request.
	 * @return void
	 */
	private function assert_denied_with( array $response, string $message ): void {
		$this->assertFalse( $response['success'] );
		$this->assertStringContainsString( $message, $this->_last_response );
	}

	/**
	 * Give the given role the frontend highlighter capability and return its user ID.
	 *
	 * The precondition is asserted by the caller, so a denial can only be attributed to the rest
	 * of the guard rather than to a capability that was never granted.
	 *
	 * @param string $role The role to grant edac_view_frontend_highlighter to.
	 * @return int The current user ID.
	 */
	private function login_with_highlighter_capability( string $role = 'editor' ): int {
		edac_ignore_capability()->sync_matrix( [ 'edac_view_frontend_highlighter' => [ $role ] ] );

		$user_id = self::factory()->user->create( [ 'role' => $role ] );
		wp_set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * An editor without edac_view_frontend_highlighter must be denied, even though
	 * editors satisfy read_post/edit_post on virtually any post - read_post alone
	 * was previously sufficient here, bypassing the Permissions tab setting.
	 */
	public function testEditorWithoutCapabilityIsDenied(): void {
		edac_ignore_capability()->sync_matrix( [] );

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		$_POST['nonce']   = wp_create_nonce( 'frontend-highlighter' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_frontend_highlight_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
	}

	/**
	 * An editor who holds the capability is allowed through.
	 */
	public function testEditorWithCapabilityIsAllowed(): void {
		edac_ignore_capability()->sync_matrix( [ 'edac_view_frontend_highlighter' => [ 'editor' ] ] );

		$editor_id = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		$_POST['nonce']   = wp_create_nonce( 'frontend-highlighter' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_frontend_highlight_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
	}

	/**
	 * The authenticated endpoint is registered unconditionally; the logged-out one only when the
	 * visibility filter opts in, which is what lets a site expose the highlighter anonymously.
	 */
	public function test_init_hooks_registers_the_nopriv_endpoint_only_when_the_filter_opts_in(): void {
		$frontend_highlight = new Frontend_Highlight();

		remove_all_filters( 'edac_filter_frontend_highlighter_visibility' );
		$nopriv_before = has_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' );

		$frontend_highlight->init_hooks();

		$this->assertSame(
			10,
			has_action( 'wp_ajax_edac_frontend_highlight_ajax', [ $frontend_highlight, 'ajax' ] )
		);
		$this->assertSame( $nopriv_before, has_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' ) );

		add_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );
		$frontend_highlight->init_hooks();

		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax' ) );

		remove_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );
		remove_action( 'wp_ajax_nopriv_edac_frontend_highlight_ajax', [ $frontend_highlight, 'ajax' ] );
	}

	/**
	 * A request whose nonce does not verify is rejected even when everything else about it is
	 * valid, so the nonce is the only reason the capable editor below is turned away.
	 */
	public function test_ajax_rejects_a_request_with_an_invalid_nonce(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$response = $this->dispatch_highlighter_ajax(
			[
				'post_id' => self::$post_id,
				'nonce'   => 'not-a-valid-nonce',
			]
		);

		$this->assert_denied_with( $response, 'Permission Denied' );
	}

	/**
	 * A request without a post_id is rejected before any post or issue lookup happens.
	 */
	public function test_ajax_rejects_a_request_without_a_post_id(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$response = $this->dispatch_highlighter_ajax( [] );

		$this->assert_denied_with( $response, 'The id value was not set' );
	}

	/**
	 * A request naming a post that does not exist is rejected rather than treated as post 0.
	 */
	public function test_ajax_rejects_a_request_for_a_post_that_does_not_exist(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => 987654321 ] );

		$this->assert_denied_with( $response, 'Post not found' );
	}

	/**
	 * A password protected post is denied to a user who cannot read it, ahead of any capability
	 * or capability-only check - the editor below satisfies the highlighter capability but has
	 * not entered the post password.
	 */
	public function test_ajax_rejects_a_password_protected_post(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$post_id = self::factory()->post->create(
			[
				'post_status'   => 'publish',
				'post_password' => 's3cret',
			]
		);

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		$this->assert_denied_with( $response, 'Permission Denied' );
	}

	/**
	 * The highlighter capability alone is not enough for a logged in user: read_post is checked
	 * too, so a holder who cannot read a private post is still denied.
	 */
	public function test_ajax_rejects_a_logged_in_user_who_cannot_read_the_post(): void {
		$this->login_with_highlighter_capability( 'author' );

		// Precondition: the author really does hold the capability, so the denial below can only
		// come from the read_post half of the guard.
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$post_id = self::factory()->post->create(
			[
				'post_status' => 'private',
				'post_author' => self::factory()->user->create( [ 'role' => 'administrator' ] ),
			]
		);

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		$this->assert_denied_with( $response, 'Permission Denied' );
	}

	/**
	 * Anonymous visitors are denied while the visibility filter is left at its default.
	 */
	public function test_ajax_rejects_an_anonymous_visitor_when_the_visibility_filter_is_off(): void {
		remove_all_filters( 'edac_filter_frontend_highlighter_visibility' );
		wp_set_current_user( 0 );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => self::$post_id ] );

		$this->assert_denied_with( $response, 'Permission Denied' );
	}

	/**
	 * With the filter on, an anonymous visitor gets the data for a publicly viewable post.
	 */
	public function test_ajax_allows_an_anonymous_visitor_when_the_visibility_filter_is_on(): void {
		add_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );
		wp_set_current_user( 0 );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => self::$post_id ] );

		remove_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );

		$this->assertTrue( $response['success'] );

		$payload = json_decode( $response['data'], true );
		$this->assertNotEmpty( $payload['issues'] );
	}

	/**
	 * The filter alone is not enough for an anonymous visitor: the post also has to be publicly
	 * viewable, so a draft is still denied.
	 */
	public function test_ajax_rejects_an_anonymous_visitor_for_a_post_that_is_not_publicly_viewable(): void {
		add_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );
		wp_set_current_user( 0 );

		$post_id  = self::factory()->post->create( [ 'post_status' => 'draft' ] );
		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		remove_filter( 'edac_filter_frontend_highlighter_visibility', '__return_true' );

		$this->assert_denied_with( $response, 'Permission Denied' );
	}

	/**
	 * A post with no stored issues reports an empty query rather than an empty payload.
	 */
	public function test_ajax_reports_an_empty_issue_query(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		$this->assert_denied_with( $response, 'Issue query returned no results' );
	}

	/**
	 * Rows whose rule is no longer in the registry are filtered out by get_issues(), so the
	 * endpoint reports "no results" rather than an empty issue list.
	 */
	public function test_ajax_reports_no_results_when_every_row_belongs_to_an_unknown_rule(): void {
		$this->login_with_highlighter_capability();
		$this->assertTrue( edac_user_can_use_frontend_highlighter() );

		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->insert_issue( $post_id, 'not_a_registered_rule' );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		$this->assert_denied_with( $response, 'Issue query returned no results' );
	}

	/**
	 * Each row is mapped onto its rule's metadata, the ignore flag replaces the rule's own type
	 * and the WCAG title is resolved from the number the rule declares.
	 */
	public function test_ajax_maps_each_issue_onto_its_rule_metadata(): void {
		$this->login_with_highlighter_capability();

		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->insert_issue(
			$post_id,
			'empty_paragraph_tag',
			[
				'object'            => 'a &amp; b',
				'selector'          => 'p.p1',
				'landmark'          => 'main',
				'landmark_selector' => '#content',
			]
		);
		$this->insert_issue( $post_id, 'img_alt_missing', [ 'ignre' => 1 ] );

		$response = $this->dispatch_highlighter_ajax( [ 'post_id' => $post_id ] );

		$this->assertTrue( $response['success'] );

		$payload = json_decode( $response['data'], true );
		$this->assertCount( 2, $payload['issues'] );

		$issues = array_column( $payload['issues'], null, 'slug' );

		$warning = $issues['empty_paragraph_tag'];
		$this->assertSame( 'warning', $warning['rule_type'] );
		$this->assertSame( 'Empty Paragraph Tag', $warning['rule_title'] );
		$this->assertSame( '0.1', $warning['wcag'] );
		$this->assertSame( 'Best Practice', $warning['wcag_title'] );
		$this->assertSame( 4, $warning['severity'] );
		$this->assertSame( 0, (int) $warning['ignored'] );
		// The object is stored escaped and is decoded for the frontend.
		$this->assertSame( 'a & b', $warning['object'] );
		$this->assertSame( 'p.p1', $warning['selector'] );
		$this->assertSame( 'main', $warning['landmark'] );
		$this->assertSame( '#content', $warning['landmark_selector'] );
		$this->assertStringContainsString( 'https://a11ychecker.com/help7870', $warning['link'] );
		$this->assertStringContainsString( 'utm_campaign=frontend-highlighter', $warning['link'] );

		$ignored = $issues['img_alt_missing'];
		// A row flagged as ignored reports as ignored regardless of the rule's own type.
		$this->assertSame( 'ignored', $ignored['rule_type'] );
		$this->assertSame( 1, (int) $ignored['ignored'] );
		$this->assertSame( '1.1.1', $ignored['wcag'] );
		$this->assertSame( 'Non-text Content', $ignored['wcag_title'] );
		$this->assertSame( 1, $ignored['severity'] );

		// Neither rule declares fixes, so no fix fields are assembled for the payload.
		$this->assertSame( [], $payload['fixes'] );
	}

	/**
	 * Returns null, not an empty array, when the post has no rows at all - that is what tells the
	 * endpoint "nothing stored" apart from "everything filtered out" - and drops the rows whose
	 * rule is no longer registered while keeping the ones that are.
	 */
	public function test_get_issues_filters_rows_to_registered_rules(): void {
		$empty_post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->assertNull( $this->frontend_highlight->get_issues( $empty_post_id ) );

		$post_id = self::factory()->post->create( [ 'post_status' => 'publish' ] );
		$this->insert_issue( $post_id, 'not_a_registered_rule' );
		$this->insert_issue( $post_id, 'img_alt_missing' );

		$issues = (array) $this->frontend_highlight->get_issues( $post_id );

		$this->assertSame( [ 'img_alt_missing' ], array_column( $issues, 'rule' ) );
	}
}
