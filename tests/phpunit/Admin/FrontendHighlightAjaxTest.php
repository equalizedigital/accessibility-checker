<?php
/**
 * AJAX behavior tests for the frontend highlighter's data endpoint.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Frontend_Highlight;
use EDAC\Admin\Update_Database;

/**
 * Tests for Frontend_Highlight::ajax() capability enforcement (PRO-1290).
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
	 * A dismissed issue carries the details the highlighter's dismiss panel shows,
	 * plus its original rule type so a reopen can restore it.
	 */
	public function testDismissedIssueIncludesDismissalDetails(): void {
		$admin_id = self::factory()->user->create(
			[
				'role'       => 'administrator',
				'user_login' => 'dismisser',
			]
		);
		wp_set_current_user( $admin_id );

		global $wpdb;
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'accessibility_checker',
			[
				'postid'        => self::$post_id,
				'siteid'        => get_current_blog_id(),
				'rule'          => 'empty_paragraph_tag',
				'ruletype'      => 'error',
				'object'        => 'dismissed',
				'selector'      => 'body',
				'ignre'         => 1,
				'ignre_user'    => $admin_id,
				'ignre_date'    => '2026-09-30 10:00:00',
				'ignre_reason'  => 'false_positive',
				'ignre_comment' => esc_html( 'Not <b>real</b>' ),
				'ignre_global'  => 0,
			]
		);
		$dismissed_id = (string) $wpdb->insert_id;

		$_POST['nonce']   = wp_create_nonce( 'frontend-highlighter' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_frontend_highlight_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );

		$issues = json_decode( $response['data'], true )['issues'];
		$issue  = current( wp_list_filter( $issues, [ 'id' => $dismissed_id ] ) );

		$this->assertSame( 'ignored', $issue['rule_type'] );
		$this->assertSame( 'warning', $issue['base_rule_type'] ); // empty_paragraph_tag's rule type.
		$this->assertSame( 'false_positive', $issue['ignre_reason'] );
		$this->assertSame( 'Not <b>real</b>', $issue['ignre_comment'] );
		$this->assertSame( 'dismisser', $issue['ignre_user_name'] );
		$this->assertNotEmpty( $issue['ignre_date'] );
		$this->assertSame( 0, $issue['ignre_global'] );
	}
}
