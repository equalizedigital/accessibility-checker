<?php
/**
 * AJAX summary behavior tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;
use EDAC\Admin\Update_Database;

/**
 * Tests for the AJAX summary response.
 *
 * Deliberately carries no @covers annotation: a class-level block makes PHPUnit
 * discard the coverage of everything the class autoloads, and these tests are
 * the first to boot the rule registry from an AJAX request (see #1995).
 */
class AjaxSummaryTest extends WP_Ajax_UnitTestCase {

	/**
	 * Prose whose Flesch-Kincaid grade sits below the ninth-grade boundary the
	 * handler compares against.
	 */
	private const SIMPLE_CONTENT = 'The cat sat on the mat. The dog ran to the van. We can see the sun.';

	/**
	 * Polysyllabic prose whose Flesch-Kincaid grade sits above the ninth-grade
	 * boundary the handler compares against.
	 */
	private const COMPLEX_CONTENT = 'Notwithstanding the aforementioned considerations, the comprehensive implementation of accessibility remediation strategies necessitates meticulous evaluation of interdependent organisational infrastructure and documentation requirements.';

	/**
	 * AJAX handler under test.
	 *
	 * @var Ajax
	 */
	private $ajax;

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	protected static $admin_id;

	/**
	 * Subscriber user ID, used for the capability guard.
	 *
	 * @var int
	 */
	protected static $subscriber_id;

	/**
	 * Published post the summary is generated for.
	 *
	 * @var int
	 */
	protected static $post_id;

	/**
	 * Create the shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		( new Update_Database() )->edac_update_database();

		self::$admin_id      = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$subscriber_id = $factory->user->create( [ 'role' => 'subscriber' ] );
		self::$post_id       = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => self::SIMPLE_CONTENT,
			]
		);
	}

	/**
	 * Remove the shared fixtures.
	 *
	 * @return void
	 */
	public static function wpTearDownAfterClass() {
		self::delete_issue_rows( self::$post_id );

		wp_delete_post( self::$post_id, true );
		wp_delete_user( self::$admin_id );
		wp_delete_user( self::$subscriber_id );
	}

	/**
	 * Reset the state each test depends on.
	 *
	 * The issue table is shared with other test classes, so a row one of them
	 * left behind for this post would make the clean-post expectations wrong.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );

		self::delete_issue_rows( self::$post_id );
		delete_post_meta( self::$post_id, '_edac_simplified_summary' );
		delete_option( 'edac_simplified_summary_prompt' );

		$this->ajax = new Ajax();
		add_action( 'wp_ajax_edac_summary_ajax', [ $this->ajax, 'summary' ] );
	}

	/**
	 * Remove the handler and the option the summary reads.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_summary_ajax', [ $this->ajax, 'summary' ] );

		delete_option( 'edac_simplified_summary_prompt' );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * The nonce guard rejects a request with no nonce and one with a wrong nonce.
	 *
	 * @return void
	 */
	public function test_summary_rejects_a_request_without_a_valid_nonce(): void {
		$requests = [
			'no nonce at all' => [],
			'wrong nonce'     => [ 'nonce' => 'not-a-real-nonce' ],
		];

		foreach ( $requests as $case => $request ) {
			$response = $this->dispatch_summary( $request + [ 'post_id' => self::$post_id ] );

			$this->assertFalse( $response['success'], $case );
			$this->assert_summary_error( $response, 'Permission Denied' );
		}
	}

	/**
	 * The post ID guard rejects a request that does not name a post.
	 *
	 * @return void
	 */
	public function test_summary_rejects_a_request_without_a_post_id(): void {
		$response = $this->dispatch_summary( [ 'nonce' => wp_create_nonce( 'ajax-nonce' ) ] );

		$this->assertFalse( $response['success'] );
		$this->assert_summary_error( $response, 'The post ID was not set' );
	}

	/**
	 * The capability guard rejects a user who cannot edit the requested post.
	 *
	 * @return void
	 */
	public function test_summary_rejects_a_user_who_cannot_edit_the_post(): void {
		wp_set_current_user( self::$subscriber_id );

		$response = $this->dispatch_summary(
			[
				'nonce'   => wp_create_nonce( 'ajax-nonce' ),
				'post_id' => self::$post_id,
			]
		);

		$this->assertFalse( $response['success'] );
		$this->assert_summary_error( $response, 'You do not have permission to view this information for this post.' );
	}

	/**
	 * A post with no recorded issues reports every rule as passed.
	 *
	 * @return void
	 */
	public function test_summary_reports_a_fully_passing_post(): void {
		$html = $this->request_summary_html( self::$post_id );

		$this->assertStringContainsString( '<ul class="edac-summary-grid">', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">100% Passed Checks</span>', $html );
		$this->assertStringContainsString( 'edac-summary-total-progress-circle over50', $html );
		$this->assertStringNotContainsString( ' has-errors', $html );
		$this->assertStringContainsString( 'True accessibility requires manual testing', $html );
	}

	/**
	 * The over-fifty progress modifier and the tile error states all flip once
	 * the rules have issues for the post.
	 *
	 * @return void
	 */
	public function test_summary_marks_the_stats_when_the_post_has_issues(): void {
		$slugs = array_values( array_filter( array_map( 'strval', wp_list_pluck( edac_register_rules(), 'slug' ) ) ) );
		$this->assertNotEmpty( $slugs, 'The rule registry must be loaded for this test to mean anything.' );

		foreach ( $slugs as $slug ) {
			self::insert_issue_row( self::$post_id, $slug, 'error' );
		}
		// Plus one warning, so the warning tile takes its own branch of the ternary.
		self::insert_issue_row( self::$post_id, $slugs[0], 'warning' );

		$html = $this->request_summary_html( self::$post_id );

		// No rule passes, so the progress circle drops the over-50 modifier.
		$this->assertStringNotContainsString( 'over50', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">0% Passed Checks</span>', $html );
		$this->assertStringContainsString( 'edac-summary-stat edac-summary-errors has-errors', $html );
		$this->assertStringContainsString( 'edac-summary-stat edac-summary-warnings has-errors', $html );
	}

	/**
	 * Contrast failures are subtracted from the error tile upstream, so they are
	 * reported once rather than twice.
	 *
	 * @return void
	 */
	public function test_summary_excludes_contrast_failures_from_the_problem_count(): void {
		self::insert_issue_row( self::$post_id, 'color_contrast_failure', 'error' );

		$html = $this->request_summary_html( self::$post_id );

		$this->assertStringContainsString( 'edac-summary-stat edac-summary-contrast has-errors', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">1 Contrast Problem</span>', $html );
		$this->assertStringNotContainsString( 'edac-summary-stat edac-summary-errors has-errors', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">0 Problems</span>', $html );
	}

	/**
	 * Every readability summary state the handler can render.
	 *
	 * @dataProvider data_simplified_summary_states
	 *
	 * @param string $prompt         Value for the edac_simplified_summary_prompt option.
	 * @param string $content        Post content, which drives the content grade.
	 * @param string $summary_meta   Value for the _edac_simplified_summary meta.
	 * @param string $expected_text  The readability summary sentence expected.
	 * @param string $expected_class The summary line's class attribute suffix.
	 * @return void
	 */
	public function test_summary_readability_text_for_each_simplified_summary_state( string $prompt, string $content, string $summary_meta, string $expected_text, string $expected_class ): void {
		update_option( 'edac_simplified_summary_prompt', $prompt );

		$post_id = self::factory()->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => $content,
			]
		);

		if ( '' !== $summary_meta ) {
			update_post_meta( $post_id, '_edac_simplified_summary', $summary_meta );
		}

		$html = $this->request_summary_html( $post_id );

		$this->assertStringContainsString( $expected_text, $html );
		$this->assertStringContainsString( $expected_class, $html );
	}

	/**
	 * Data provider for the readability summary states.
	 *
	 * @return array<string, array{string, string, string, string, string}>
	 */
	public static function data_simplified_summary_states(): array {
		return [
			'prompt disabled'               => [ 'none', self::SIMPLE_CONTENT, '', 'A Simplified summary has not been included for this content.', 'edac-summary-readability-summary-text active"' ],
			'content already readable'      => [ 'after', self::SIMPLE_CONTENT, '', 'Your content has a reading level at or below 9th grade and does not require a simplified summary.', 'edac-summary-readability-summary-text active"' ],
			'long content, no summary'      => [ 'after', self::COMPLEX_CONTENT, '', 'A Simplified summary has not been included for this content.', 'edac-summary-readability-summary-text"' ],
			'summary supplied and readable' => [ 'after', self::COMPLEX_CONTENT, self::SIMPLE_CONTENT, 'A simplified summary has been included for this content.', 'edac-summary-readability-summary-text active"' ],
			'summary grade too high'        => [ 'after', self::COMPLEX_CONTENT, self::COMPLEX_CONTENT, 'The reading level of the simplified summary is too high.', 'edac-summary-readability-summary-text"' ],
		];
	}

	/**
	 * The readability block is hidden for virtual pages and shown for everything
	 * else.
	 *
	 * @return void
	 */
	public function test_summary_hides_the_readability_block_for_a_virtual_page(): void {
		self::create_mock_pro_virtual_item_type();
		register_post_type(
			'edac_virtual_item',
			[
				'public'   => false,
				'supports' => [ 'title' ],
			]
		);

		$virtual_post_id = self::factory()->post->create(
			[
				'post_type'    => 'edac_virtual_item',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => self::SIMPLE_CONTENT,
			]
		);
		$this->assertTrue( edac_is_virtual_page( $virtual_post_id ) );

		$virtual_html = $this->request_summary_html( $virtual_post_id );
		$this->assertStringContainsString( 'edac-summary-readability" style="display: none;"', $virtual_html );

		// Counterfactual: an ordinary post renders the same block visible.
		$regular_html = $this->request_summary_html( self::$post_id );
		$this->assertStringNotContainsString( 'style="display: none;"', $regular_html );
	}

	/**
	 * Send the summary request the admin page sends and return the decoded body.
	 *
	 * Assigns the superglobals wholesale and clears the captured response first,
	 * because _handleAjax() leaves $_REQUEST and $_last_response behind between
	 * tests.
	 *
	 * @param array $payload The request payload.
	 * @return array The decoded JSON body.
	 */
	private function dispatch_summary( array $payload ): array {
		$_POST                = $payload;
		$_REQUEST             = $payload;
		$this->_last_response = '';

		try {
			$this->_handleAjax( 'edac_summary_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertIsArray( $response );

		return $response;
	}

	/**
	 * Dispatch an authenticated summary request and unwrap its markup.
	 *
	 * @param int $post_id The post to summarise.
	 * @return string The rendered markup.
	 */
	private function request_summary_html( int $post_id ): string {
		$response = $this->dispatch_summary(
			[
				'nonce'   => wp_create_nonce( 'ajax-nonce' ),
				'post_id' => $post_id,
			]
		);

		$this->assertTrue( $response['success'] );
		$this->assertIsString( $response['data'] );
		$this->assertJson( $response['data'] );

		$payload = json_decode( $response['data'], true );
		$this->assertArrayHasKey( 'content', $payload );
		$this->assertIsString( $payload['content'] );

		return $payload['content'];
	}

	/**
	 * Assert a rejection branch by the message it chose.
	 *
	 * WP_Error codes are not asserted: wp_send_json_error() reshapes them across
	 * WordPress versions, while the message is picked per branch here.
	 *
	 * @param array  $response The decoded JSON body.
	 * @param string $message  The message this branch must produce.
	 * @return void
	 */
	private function assert_summary_error( array $response, string $message ): void {
		$this->assertStringContainsString( $message, (string) wp_json_encode( $response ) );
	}

	/**
	 * Record an issue row for a post.
	 *
	 * @param int    $post_id  The post the issue belongs to.
	 * @param string $rule     The rule slug.
	 * @param string $ruletype The rule type.
	 * @return void
	 */
	private static function insert_issue_row( int $post_id, string $rule, string $ruletype ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Fixtures for the summary counts, which read this table directly.
			$wpdb->prefix . 'accessibility_checker',
			[
				'postid'        => $post_id,
				'siteid'        => get_current_blog_id(),
				'type'          => 'post',
				'rule'          => $rule,
				'ruletype'      => $ruletype,
				'object'        => '<a></a>',
				'selector'      => 'a[href="#"]',
				'recordcheck'   => 1,
				'user'          => self::$admin_id,
				'ignre'         => 0,
				'ignre_global'  => 0,
				'ignre_user'    => null,
				'ignre_date'    => null,
				'ignre_reason'  => null,
				'ignre_comment' => null,
			]
		);
	}

	/**
	 * Drop every issue row recorded for a post on this site.
	 *
	 * @param int $post_id The post to clear.
	 * @return void
	 */
	private static function delete_issue_rows( int $post_id ): void {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clearing fixtures this suite owns; the table is shared with other test classes.
			$wpdb->prefix . 'accessibility_checker',
			[
				'postid' => $post_id,
				'siteid' => get_current_blog_id(),
			]
		);
	}

	/**
	 * Stand in for the Pro virtual item type so edac_is_virtual_page() reports
	 * true. Mirrors the mock in IsVirtualPageTest.
	 *
	 * @return void
	 */
	private static function create_mock_pro_virtual_item_type(): void {
		if ( class_exists( '\EqualizeDigital\AccessibilityCheckerPro\VirtualContent\PostType\VirtualItemType' ) ) {
			return;
		}

		// phpcs:ignore Squiz.PHP.Eval.Discouraged -- The Pro class cannot be loaded here; a stub is the only way to reach the branch.
		eval(
			'
			namespace EqualizeDigital\AccessibilityCheckerPro\VirtualContent\PostType;
			class VirtualItemType {
				const POST_TYPE = "edac_virtual_item";
			}
			'
		);
	}
}
