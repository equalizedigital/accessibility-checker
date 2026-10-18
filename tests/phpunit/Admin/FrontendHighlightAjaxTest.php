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
}
