<?php
/**
 * AJAX details behavior tests.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Ajax;
use EDAC\Admin\Update_Database;

/**
 * Tests for the AJAX details response.
 */
class AjaxDetailsTest extends WP_Ajax_UnitTestCase {

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
		( new Update_Database() )->edac_update_database();

		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		self::$post_id  = $factory->post->create(
			[
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => self::$admin_id,
				'post_content' => '<p>Content for AJAX details tests.</p>',
			]
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'accessibility_checker';
		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Clearing prior fixtures for this focused AJAX regression test.
			$table_name,
			[
				'postid' => self::$post_id,
				'siteid' => get_current_blog_id(),
				'rule'   => 'empty_link',
			]
		);
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Using direct query for a focused AJAX regression test.
			$table_name,
			[
				'postid'        => self::$post_id,
				'siteid'        => get_current_blog_id(),
				'type'          => 'post',
				'rule'          => 'empty_link',
				'ruletype'      => 'error',
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
	 * Set up before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::$admin_id );

		$this->ajax = new Ajax();
		add_action( 'wp_ajax_edac_details_ajax', [ $this->ajax, 'details' ] );
	}

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		remove_action( 'wp_ajax_edac_details_ajax', [ $this->ajax, 'details' ] );
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Verify the expand button references the rendered rule heading.
	 *
	 * @return void
	 */
	public function test_details_expand_button_is_labelled_by_rule_heading(): void {
		$_POST['nonce']   = wp_create_nonce( 'ajax-nonce' );
		$_POST['post_id'] = self::$post_id;

		try {
			$this->_handleAjax( 'edac_details_ajax' );
		} catch ( WPAjaxDieContinueException $exception ) {
			$this->assertNotEmpty( $this->_last_response );
		}

		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );

		$html = json_decode( $response['data'] );
		$this->assertIsString( $html );

		$document              = new DOMDocument();
		$internal_errors_state = libxml_use_internal_errors( true );
		$document->loadHTML( '<div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $internal_errors_state );

		$xpath   = new DOMXPath( $document );
		$heading = $xpath->query( '//h3[@id="edac-details-rule-heading-empty_link"]' );
		$button  = $xpath->query( '//button[@aria-labelledby="edac-details-rule-heading-empty_link"]' );

		$this->assertSame( 1, $heading->length );
		$this->assertSame( 1, $button->length );
		$heading_markup = $document->saveHTML( $heading->item( 0 ) );
		$heading_text   = preg_replace( '/\s+/', ' ', $heading->item( 0 )->textContent );

		$this->assertIsString( $heading_text );
		$this->assertStringContainsString( '1', $heading_text );
		$this->assertStringContainsString( 'total', $heading_markup );
		$this->assertStringContainsString( 'Critical', $heading_text );
	}
}
